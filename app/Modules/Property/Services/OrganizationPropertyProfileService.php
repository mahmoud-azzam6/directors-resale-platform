<?php

declare(strict_types=1);

namespace App\Modules\Property\Services;

use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\{OrganizationPropertyRepository,OrganizationPropertyProfileRepository,PropertyMeasurementRepository,PropertyAttributeValueRepository,PropertyCatalogRepository,UnitTypeConfigurationRepository,MeasurementDefinitionRepository,AttributeDefinitionRepository,GeographicLocationRepository,DevelopmentCatalogRepository};
use DateTimeImmutable;

final class OrganizationPropertyProfileService
{
    public function __construct(
        private OrganizationPropertyRepository $properties,
        private OrganizationPropertyProfileRepository $profiles,
        private PropertyMeasurementRepository $measurements,
        private PropertyAttributeValueRepository $attributeValues,
        private PropertyCatalogRepository $catalogs,
        private UnitTypeConfigurationRepository $configurations,
        private MeasurementDefinitionRepository $measurementDefinitions,
        private AttributeDefinitionRepository $attributeDefinitions,
        private GeographicLocationRepository $locations,
        private DevelopmentCatalogRepository $developments,
        private DatabaseConnectionInterface $database,
    ) {}

    public function getProfile(int|string $propertyId, int|string $organizationId): array
    {
        $property = $this->need($this->properties->findInOrganization($propertyId, $organizationId), 'PROPERTY_NOT_FOUND');
        return $this->aggregate($property, $this->profiles->findByOrganizationPropertyId($property['id']));
    }

    public function saveProfile(int|string $propertyId, int|string $organizationId, array $data, int|string $actorId): array
    {
        return $this->database->transaction(function () use ($propertyId, $organizationId, $data, $actorId): array {
            $property = $this->need($this->properties->findForUpdate($propertyId, $organizationId), 'PROPERTY_NOT_FOUND');
            if ($property['status'] === 'archived') $this->fail('PROPERTY_ARCHIVED');
            $actor = $this->id($actorId, 'PROPERTY_NOT_FOUND');
            $profile = $this->profiles->findByOrganizationPropertyIdForUpdate($property['id']);
            if ($profile !== null) {
                if (!array_key_exists('expected_revision', $data) || $this->id($data['expected_revision'], 'PROFILE_REVISION_CONFLICT') !== $profile['revision']) $this->fail('PROFILE_REVISION_CONFLICT');
            }
            $this->assertKeys($data);
            $current = $profile ?? ['property_category_id'=>null,'unit_type_id'=>null,'accepted_configuration_version_id'=>null,'geographic_location_id'=>null,'development_reference_type'=>null,'developer_id'=>null,'project_id'=>null,'project_phase_id'=>null];
            $next = $current;
            foreach (['property_category_id','unit_type_id','geographic_location_id'] as $field) if (array_key_exists($field, $data)) $next[$field] = $data[$field] === null ? null : $this->id($data[$field], 'CATALOG_ITEM_NOT_FOUND');
            $this->development($next, $data);
            $categoryChanged = $current['property_category_id'] !== $next['property_category_id'];
            $unitChanged = $current['unit_type_id'] !== $next['unit_type_id'];
            $category = $next['property_category_id'] === null ? null : $this->need($this->catalogs->findCategoryById($next['property_category_id']), 'CATALOG_ITEM_NOT_FOUND');
            if ($categoryChanged && $category !== null && $category['status'] !== 'active') $this->fail('CATALOG_ITEM_INACTIVE');
            if ($next['unit_type_id'] !== null) {
                $unit = $this->need($this->catalogs->findUnitTypeById($next['unit_type_id']), 'CATALOG_ITEM_NOT_FOUND');
                if ($unitChanged && $unit['status'] !== 'active') $this->fail('CATALOG_ITEM_INACTIVE');
                if ($category === null || $unit['property_category_id'] !== $category['id']) $this->fail('CATEGORY_UNIT_TYPE_MISMATCH');
            } elseif ($current['unit_type_id'] !== null && $categoryChanged) $this->fail('CATEGORY_UNIT_TYPE_MISMATCH');
            if ($next['geographic_location_id'] !== null && array_key_exists('geographic_location_id', $data)) $this->active($this->locations->findGeographicLocationById($next['geographic_location_id']));

            $hasValues = $profile !== null && ($this->measurements->listForProperty($property['id']) !== [] || $this->attributeValues->listForProperty($property['id']) !== []);
            $replacement = ($data['replace_values'] ?? false) === true;
            if ($unitChanged && $hasValues && !$replacement) $this->fail('UNIT_TYPE_REPLACEMENT_REQUIRED');
            if ($unitChanged && $hasValues && (!array_key_exists('measurements', $data) || !array_key_exists('attributes', $data))) $this->fail('UNIT_TYPE_REPLACEMENT_REQUIRED');
            if ($next['unit_type_id'] !== null && ($current['accepted_configuration_version_id'] === null || $unitChanged)) {
                $configuration = $this->configurations->findActiveConfigurationForUnitType($next['unit_type_id']);
                if ($configuration === null) $this->fail('NO_ACTIVE_CONFIGURATION');
                $next['accepted_configuration_version_id'] = $configuration['id'];
            }
            if ($next['unit_type_id'] === null) $next['accepted_configuration_version_id'] = null;
            if (($data['measurements'] ?? []) !== [] || ($data['attributes'] ?? []) !== []) {
                if ($next['accepted_configuration_version_id'] === null) $this->fail('NO_ACTIVE_CONFIGURATION');
            }
            $config = $next['accepted_configuration_version_id'] === null ? null : $this->configurations->findConfigurationById($next['accepted_configuration_version_id']);
            if ($config !== null && $config['unit_type_id'] !== $next['unit_type_id']) $this->fail('NO_ACTIVE_CONFIGURATION');

            if ($profile === null) {
                $profile = $this->profiles->create($next + ['organization_property_id'=>$property['id'],'created_by_user_id'=>$actor,'updated_by_user_id'=>$actor]);
            } else {
                $changes = [];
                foreach (['property_category_id','unit_type_id','accepted_configuration_version_id','geographic_location_id','development_reference_type','developer_id','project_id','project_phase_id'] as $field) if ($next[$field] !== $current[$field]) $changes[$field] = $next[$field];
                if ($changes !== [] || $this->hasValueOperation($data)) {
                    $changes['updated_by_user_id'] = $actor;
                    $profile = $this->need($this->profiles->updateWithExpectedRevision($property['id'], $profile['revision'], $changes), 'PROFILE_REVISION_CONFLICT');
                }
            }
            if ($unitChanged && $hasValues) { $this->measurements->deleteAllForProperty($property['id']); $this->attributeValues->deleteAllForProperty($property['id']); }
            if ($config !== null) {
                $this->saveMeasurements($property['id'], $config['id'], $data, $actor);
                $this->saveAttributes($property['id'], $config['id'], $data, $actor);
            }
            return $this->aggregate($property, $profile);
        });
    }

    private function development(array &$next, array $data): void
    {
        if (!array_key_exists('development_reference_type', $data)) return;
        $type = $data['development_reference_type'];
        foreach (['development_reference_type','developer_id','project_id','project_phase_id'] as $f) $next[$f] = null;
        if ($type === null) return;
        if (!in_array($type, ['DEVELOPER','PROJECT','PHASE'], true)) $this->fail('DEVELOPMENT_REFERENCE_INVALID');
        $field = $type === 'DEVELOPER' ? 'developer_id' : ($type === 'PROJECT' ? 'project_id' : 'project_phase_id');
        if (!array_key_exists($field, $data) || $data[$field] === null) $this->fail('DEVELOPMENT_REFERENCE_INVALID');
        $id = $this->id($data[$field], 'DEVELOPMENT_REFERENCE_INVALID');
        if ($type === 'DEVELOPER') $record = $this->developments->findDeveloperById($id);
        elseif ($type === 'PROJECT') $record = $this->developments->findProjectById($id);
        else $record = $this->developments->findProjectPhaseById($id);
        if ($record === null || $record['status'] !== 'active') $this->fail('DEVELOPMENT_REFERENCE_INVALID');
        $next['development_reference_type'] = $type; $next[$field] = $id;
    }

    private function saveMeasurements(int $property, int $configuration, array $data, int $actor): void
    {
        $allowed = array_fill_keys(array_column($this->configurations->listMeasurementRules($configuration), 'measurement_definition_id'), true);
        foreach ($data['clear_measurement_definition_ids'] ?? [] as $id) { $definitionId = $this->id($id, 'CONFIGURATION_VALUE_NOT_ALLOWED'); if (!isset($allowed[$definitionId])) $this->fail('CONFIGURATION_VALUE_NOT_ALLOWED'); $this->measurements->delete($property, $definitionId); }
        foreach ($data['measurements'] ?? [] as $row) {
            if (!is_array($row)) $this->fail('INVALID_MEASUREMENT_VALUE');
            $definitionId = $this->id($row['definition_id'] ?? null, 'INVALID_MEASUREMENT_VALUE');
            if (!isset($allowed[$definitionId])) $this->fail('CONFIGURATION_VALUE_NOT_ALLOWED');
            $definition = $this->need($this->measurementDefinitions->findMeasurementDefinitionById($definitionId), 'CATALOG_ITEM_NOT_FOUND');
            if ($definition['status'] !== 'active') $this->fail('CATALOG_ITEM_INACTIVE');
            if (($row['unit_code'] ?? null) !== $definition['default_unit_code']) $this->fail('MEASUREMENT_UNIT_MISMATCH');
            $value = $this->decimal($row['value'] ?? null, true, 'INVALID_MEASUREMENT_VALUE');
            $this->measurements->upsert(['organization_property_id'=>$property,'measurement_definition_id'=>$definitionId,'value_decimal'=>$value,'unit_code'=>$definition['default_unit_code'],'created_by_user_id'=>$actor,'updated_by_user_id'=>$actor]);
        }
    }

    private function saveAttributes(int $property, int $configuration, array $data, int $actor): void
    {
        $allowed = array_fill_keys(array_column($this->configurations->listAttributeRules($configuration), 'attribute_definition_id'), true);
        foreach ($data['clear_attribute_definition_ids'] ?? [] as $id) { $definitionId = $this->id($id, 'CONFIGURATION_VALUE_NOT_ALLOWED'); if (!isset($allowed[$definitionId])) $this->fail('CONFIGURATION_VALUE_NOT_ALLOWED'); $this->attributeValues->delete($property, $definitionId); }
        foreach ($data['attributes'] ?? [] as $row) {
            if (!is_array($row)) $this->fail('ATTRIBUTE_TYPE_MISMATCH');
            $definitionId = $this->id($row['definition_id'] ?? null, 'ATTRIBUTE_TYPE_MISMATCH');
            if (!isset($allowed[$definitionId])) $this->fail('CONFIGURATION_VALUE_NOT_ALLOWED');
            $definition = $this->need($this->attributeDefinitions->findAttributeDefinitionById($definitionId), 'CATALOG_ITEM_NOT_FOUND');
            if ($definition['status'] !== 'active') $this->fail('CATALOG_ITEM_INACTIVE');
            $type = $definition['data_type']; $value = $row['value'] ?? null; $option = null;
            if ($type === 'INTEGER') { if (!is_int($value)) $this->fail('ATTRIBUTE_TYPE_MISMATCH'); $value = $value; }
            elseif ($type === 'DECIMAL') $value = $this->decimal($value, false, 'ATTRIBUTE_TYPE_MISMATCH');
            elseif ($type === 'BOOLEAN') { if (!is_bool($value)) $this->fail('ATTRIBUTE_TYPE_MISMATCH'); }
            elseif ($type === 'TEXT') { if (!is_string($value) || (isset($definition['text_max_length']) && $definition['text_max_length'] !== null && mb_strlen($value) > (int)$definition['text_max_length'])) $this->fail('ATTRIBUTE_TYPE_MISMATCH'); }
            elseif ($type === 'DATE') { $date = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false; if ($date === false || $date->format('Y-m-d') !== $value) $this->fail('ATTRIBUTE_TYPE_MISMATCH'); }
            elseif ($type === 'ENUM') { $option = $this->attributeDefinitions->findAttributeOptionById($this->id($row['option_id'] ?? null, 'ENUM_OPTION_INVALID')); if ($option === null || $option['attribute_definition_id'] !== $definitionId || $option['status'] !== 'active') $this->fail('ENUM_OPTION_INVALID'); $value = null; }
            else $this->fail('ATTRIBUTE_TYPE_MISMATCH');
            $this->attributeValues->upsert(['organization_property_id'=>$property,'attribute_definition_id'=>$definitionId,'data_type'=>$type,'value_integer'=>$type==='INTEGER'?$value:null,'value_decimal'=>$type==='DECIMAL'?$value:null,'value_boolean'=>$type==='BOOLEAN'?$value:null,'value_text'=>$type==='TEXT'?$value:null,'value_date'=>$type==='DATE'?$value:null,'attribute_option_id'=>$option['id']??null,'created_by_user_id'=>$actor,'updated_by_user_id'=>$actor]);
        }
    }

    private function aggregate(array $property, ?array $profile): array
    {
        $result=['property'=>$property,'profile'=>$profile,'configuration'=>null,'category'=>null,'unit_type'=>null,'geography'=>null,'development'=>null,'measurements'=>$this->measurements->listForProperty($property['id']),'attributes'=>$this->attributeValues->listForProperty($property['id'])];
        if ($profile === null) return $result;
        if ($profile['property_category_id'] !== null) $result['category']=$this->catalogs->findCategoryById($profile['property_category_id']);
        if ($profile['unit_type_id'] !== null) $result['unit_type']=$this->catalogs->findUnitTypeById($profile['unit_type_id']);
        if ($profile['accepted_configuration_version_id'] !== null) $result['configuration']=$this->configurations->findConfigurationById($profile['accepted_configuration_version_id']);
        if ($profile['geographic_location_id'] !== null) $result['geography']=['location'=>$this->locations->findGeographicLocationById($profile['geographic_location_id']),'ancestry'=>$this->locations->loadAncestry($profile['geographic_location_id'])];
        if ($profile['development_reference_type'] === 'DEVELOPER') $result['development']=['developer'=>$this->developments->findDeveloperById($profile['developer_id'])];
        if ($profile['development_reference_type'] === 'PROJECT') { $p=$this->developments->findProjectById($profile['project_id']); $result['development']=['project'=>$p,'developer'=>$p===null?null:$this->developments->findDeveloperById($p['developer_id'])]; }
        if ($profile['development_reference_type'] === 'PHASE') { $p=$this->developments->findProjectPhaseById($profile['project_phase_id']); $project=$p===null?null:$this->developments->findProjectById($p['project_id']); $result['development']=['phase'=>$p,'project'=>$project,'developer'=>$project===null?null:$this->developments->findDeveloperById($project['developer_id'])]; }
        return $result;
    }
    private function hasValueOperation(array $data): bool { return array_key_exists('measurements',$data)||array_key_exists('attributes',$data)||($data['clear_measurement_definition_ids']??[])!==[]||($data['clear_attribute_definition_ids']??[])!==[]; }
    private function active(?array $row): array { $row=$this->need($row,'CATALOG_ITEM_NOT_FOUND'); if ($row['status']!=='active') $this->fail('CATALOG_ITEM_INACTIVE'); return $row; }
    private function decimal(mixed $value, bool $positive, string $code): string { if (!is_int($value) && !is_string($value)) $this->fail($code); $raw=(string)$value; if (!preg_match('/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]{1,4})?$/D',$raw)) $this->fail($code); $negative=str_starts_with($raw,'-'); if($negative)$raw=substr($raw,1); [$integer,$fraction]=array_pad(explode('.',$raw,2),2,''); $integer=ltrim($integer,'0'); if($integer==='')$integer='0'; if(strlen($integer)>14)$this->fail($code); $nonZero=trim($integer,'0')!==''||trim($fraction,'0')!==''; if($positive&&(!$nonZero||$negative))$this->fail($code); return ($negative?'-':'').$integer.'.'.str_pad($fraction,4,'0'); }
    private function assertKeys(array $data): void { foreach(array_keys($data) as $key) if(!in_array($key,['expected_revision','property_category_id','unit_type_id','geographic_location_id','development_reference_type','developer_id','project_id','project_phase_id','measurements','attributes','clear_measurement_definition_ids','clear_attribute_definition_ids','replace_values'],true)) $this->fail('CATALOG_ITEM_NOT_FOUND'); }
    private function id(mixed $value,string $code): int { if((!is_int($value)&&(!is_string($value)||!ctype_digit($value)))||(int)$value<1)$this->fail($code);return(int)$value; }
    private function need(?array $value,string $code): array { if($value===null)$this->fail($code); return $value; }
    private function fail(string $code): never { throw new ValidationException([$code=>$code]); }
}