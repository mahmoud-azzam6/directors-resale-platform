<?php
declare(strict_types=1);
namespace App\Modules\Property\Services;
use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\{PropertyCatalogRepository,UnitTypeConfigurationRepository,MeasurementDefinitionRepository,AttributeDefinitionRepository};
use PDOException;
final class UnitTypeConfigurationService {
 public function __construct(private PropertyCatalogRepository $units,private UnitTypeConfigurationRepository $configs,private MeasurementDefinitionRepository $measurements,private AttributeDefinitionRepository $attributes,private DatabaseConnectionInterface $db,private UlidGeneratorInterface $ulids) {}
 public function findConfiguration(int|string $id):?array{return $this->configs->findConfigurationById($id);} public function listConfigurations(int|string $unit):array{return $this->configs->listConfigurationsForUnitType($unit);} public function findActive(int|string $unit):?array{return $this->configs->findActiveConfigurationForUnitType($unit);} public function findDraft(int|string $unit):?array{return $this->configs->findDraftConfigurationForUnitType($unit);} public function aggregate(int|string $id):?array{return $this->configs->findConfigurationAggregateById($id);}
 public function createBlankDraft(int|string $unit, int|string|null $actor=null,string $provenance='SYSTEM_ADMIN'):array{return $this->createDraft($unit,false,$actor,$provenance);}
 public function cloneActiveDraft(int|string $unit,int|string|null $actor=null,string $provenance='SYSTEM_ADMIN'):array{return $this->createDraft($unit,true,$actor,$provenance);}
 private function createDraft(int|string $unit,bool $clone,int|string|null $actor,string $provenance):array{if(!in_array($provenance,['SYSTEM_ADMIN','SYSTEM_SEED'],true))$this->fail('INVALID_CATALOG_PROVENANCE');return $this->db->transaction(function()use($unit,$clone,$actor,$provenance){$u=$this->need($this->units->findUnitTypeForUpdate($unit),'CATALOG_ITEM_INACTIVE');if($u['status']!=='active')$this->fail('CATALOG_ITEM_INACTIVE');if($this->configs->findDraftConfigurationForUpdate($u['id']))$this->fail('CONFIGURATION_DRAFT_ALREADY_EXISTS');$src=$clone?$this->configs->findActiveConfigurationForUnitTypeForUpdate($u['id']):null;if($clone&&!$src)$this->fail('NO_ACTIVE_CONFIGURATION');$n=$u['last_allocated_configuration_version'];if($n>=4294967295)$this->fail('CONFIGURATION_NOT_ACTIVATABLE');$n++;$this->units->updateLastAllocatedConfigurationVersion($u['id'],$n);$d=$this->configs->createConfigurationVersion(['ulid'=>$this->ulids->generate(),'unit_type_id'=>$u['id'],'version_number'=>$n,'status'=>'draft','provenance'=>$provenance,'created_by_user_id'=>$actor,'updated_by_user_id'=>$actor]);if($src){$this->validate($src['id']);foreach($this->configs->listMeasurementRules($src['id'])as$r)$this->configs->createMeasurementRule($d['id'],['measurement_definition_id'=>$r['measurement_definition_id'],'requirement'=>$r['requirement'],'is_primary'=>$r['is_primary'],'sort_order'=>$r['sort_order'],'created_by_user_id'=>$actor,'updated_by_user_id'=>$actor]);foreach($this->configs->listAttributeRules($src['id'])as$r)$this->configs->createAttributeRule($d['id'],['attribute_definition_id'=>$r['attribute_definition_id'],'requirement'=>$r['requirement'],'sort_order'=>$r['sort_order'],'created_by_user_id'=>$actor,'updated_by_user_id'=>$actor]);}return $d;});}
    public function activate(int|string $unit, int|string $id, int|string|null $actor = null): array
    {
        return $this->db->transaction(function () use ($unit, $id, $actor): array {
            $u = $this->need($this->units->findUnitTypeForUpdate($unit), 'CATALOG_ITEM_INACTIVE');
            $d = $this->need($this->configs->findConfigurationForUpdate($id), 'CONFIGURATION_NOT_DRAFT');
            if ($d['unit_type_id'] !== $u['id'] || $d['status'] !== 'draft') {
                $this->fail('CONFIGURATION_NOT_DRAFT');
            }
            if ($u['status'] !== 'active') {
                $this->fail('CATALOG_ITEM_INACTIVE');
            }
            $this->validate($d['id']);
            $active = $this->configs->findActiveConfigurationForUnitTypeForUpdate($u['id']);
            if ($active !== null) {
                $this->need($this->configs->updateConfigurationVersion($active['id'], [
                    'status' => 'historical', 'updated_by_user_id' => $actor,
                ]), 'CONFIGURATION_NOT_ACTIVATABLE');
            }
            return $this->need($this->configs->updateConfigurationVersion($d['id'], [
                'status' => 'active', 'updated_by_user_id' => $actor,
            ]), 'CONFIGURATION_NOT_ACTIVATABLE');
        });
    }

    public function deleteDraft(int|string $unit, int|string $configuration): bool
    {
        return $this->db->transaction(function () use ($unit, $configuration): bool {
            $u = $this->need($this->units->findUnitTypeForUpdate($unit), 'CONFIGURATION_NOT_DRAFT');
            $d = $this->need($this->configs->findConfigurationForUpdate($configuration), 'CONFIGURATION_NOT_DRAFT');
            if ($d['unit_type_id'] !== $u['id'] || $d['status'] !== 'draft') {
                $this->fail('CONFIGURATION_NOT_DRAFT');
            }
            if ($d['provenance'] === 'SYSTEM_SEED') {
                $this->fail('SYSTEM_SEED_DELETE_FORBIDDEN');
            }
            foreach ($this->configs->listMeasurementRules($d['id']) as $rule) {
                $this->configs->deleteMeasurementRule($d['id'], $rule['id']);
            }
            foreach ($this->configs->listAttributeRules($d['id']) as $rule) {
                $this->configs->deleteAttributeRule($d['id'], $rule['id']);
            }
            // Committed allocation history is never decremented.
            return $this->configs->deleteConfigurationVersion($d['id']);
        });
    }
 public function addMeasurementRule(int|string $unit,int|string $configuration,array $data,int|string|null $actor=null):array{return $this->db->transaction(function()use($unit,$configuration,$data,$actor){$d=$this->draft($unit,$configuration);$definition=$this->measurement($data['measurement_definition_id']??null);if($this->configs->findMeasurementRuleByDefinition($d['id'],$definition['id']))$this->fail('DUPLICATE_CONFIGURATION_RULE');$this->measurementState($d['id'],$data['requirement']??null,$data['is_primary']??false);return $this->configs->createMeasurementRule($d['id'],['measurement_definition_id'=>$definition['id'],'requirement'=>$data['requirement'],'is_primary'=>$data['is_primary']??false,'sort_order'=>$data['sort_order']??0,'created_by_user_id'=>$actor,'updated_by_user_id'=>$actor]);});}
 public function updateMeasurementRule(int|string $unit,int|string $configuration,int|string $rule,array $data,int|string|null $actor=null):?array{return $this->db->transaction(function()use($unit,$configuration,$rule,$data,$actor){$d=$this->draft($unit,$configuration);$r=$this->need($this->configs->findMeasurementRuleById($d['id'],$rule),'CONFIGURATION_STRUCTURE_IMMUTABLE');$this->measurement($r['measurement_definition_id']);$requirement=array_key_exists('requirement',$data)?$data['requirement']:$r['requirement'];$primary=array_key_exists('is_primary',$data)?$data['is_primary']:$r['is_primary'];$this->measurementState($d['id'],$requirement,$primary,$r['id']);$changes=array_intersect_key($data,array_flip(['requirement','is_primary','sort_order']));$changes['updated_by_user_id']=$actor;return $this->configs->updateMeasurementRule($d['id'],$r['id'],$changes);});}
 public function removeMeasurementRule(int|string $unit,int|string $configuration,int|string $rule):bool{return $this->db->transaction(function()use($unit,$configuration,$rule){$d=$this->draft($unit,$configuration);$this->need($this->configs->findMeasurementRuleById($d['id'],$rule),'CONFIGURATION_STRUCTURE_IMMUTABLE');return $this->configs->deleteMeasurementRule($d['id'],$rule);});}
 public function addAttributeRule(int|string $unit,int|string $configuration,array $data,int|string|null $actor=null):array{return $this->db->transaction(function()use($unit,$configuration,$data,$actor){$d=$this->draft($unit,$configuration);$a=$this->attribute($data['attribute_definition_id']??0);if($this->configs->findAttributeRuleByDefinition($d['id'],$a['id']))$this->fail('DUPLICATE_CONFIGURATION_RULE');$r=$data['requirement']??null;if(!in_array($r,['REQUIRED','OPTIONAL'],true))$this->fail('ATTRIBUTE_DEFINITION_INACTIVE');return $this->configs->createAttributeRule($d['id'],['attribute_definition_id'=>$a['id'],'requirement'=>$r,'sort_order'=>$data['sort_order']??0,'created_by_user_id'=>$actor,'updated_by_user_id'=>$actor]);});}
 public function updateAttributeRule(int|string $unit,int|string $configuration,int|string $rule,array $data,int|string|null $actor=null):?array{return $this->db->transaction(function()use($unit,$configuration,$rule,$data,$actor){$d=$this->draft($unit,$configuration);$r=$this->need($this->configs->findAttributeRuleById($d['id'],$rule),'CONFIGURATION_STRUCTURE_IMMUTABLE');$this->attribute($r['attribute_definition_id']);$requirement=array_key_exists('requirement',$data)?$data['requirement']:$r['requirement'];if(!in_array($requirement,['REQUIRED','OPTIONAL'],true))$this->fail('ATTRIBUTE_DEFINITION_INACTIVE');$c=array_intersect_key($data,array_flip(['requirement','sort_order']));$c['updated_by_user_id']=$actor;return $this->configs->updateAttributeRule($d['id'],$r['id'],$c);});}
 public function removeAttributeRule(int|string $unit,int|string $configuration,int|string $rule):bool{return $this->db->transaction(function()use($unit,$configuration,$rule){$d=$this->draft($unit,$configuration);$this->need($this->configs->findAttributeRuleById($d['id'],$rule),'CONFIGURATION_STRUCTURE_IMMUTABLE');return $this->configs->deleteAttributeRule($d['id'],$rule);});}
 private function attribute(mixed $id):array{$a=$this->attributes->findAttributeDefinitionForUpdate($this->definitionId($id,'ATTRIBUTE_DEFINITION_INACTIVE'));if(!$a||$a['status']!=='active')$this->fail('ATTRIBUTE_DEFINITION_INACTIVE');if($a['data_type']==='ENUM'&&$this->attributes->listActiveAttributeOptionsForUpdate($a['id'])===[])$this->fail('ENUM_HAS_NO_ACTIVE_OPTIONS');return $a;}
 private function draft(int|string $unit,int|string $configuration):array{$u=$this->need($this->units->findUnitTypeForUpdate($unit),'CONFIGURATION_STRUCTURE_IMMUTABLE');$d=$this->need($this->configs->findConfigurationForUpdate($configuration),'CONFIGURATION_STRUCTURE_IMMUTABLE');if($d['unit_type_id']!==$u['id']||$d['status']!=='draft')$this->fail('CONFIGURATION_STRUCTURE_IMMUTABLE');return $d;}
 private function measurementState(int $configuration,mixed $requirement,mixed $primary,?int $ignore=null):void{if(!in_array($requirement,['REQUIRED','OPTIONAL'],true)||!in_array($primary,[true,false,0,1,'0','1'],true))$this->fail('PRIMARY_MEASUREMENT_MUST_BE_REQUIRED');$primary=(bool)$primary;if($primary&&$requirement!=='REQUIRED')$this->fail('PRIMARY_MEASUREMENT_MUST_BE_REQUIRED');if($primary)foreach($this->configs->listMeasurementRules($configuration)as$r)if($r['is_primary']&&$r['id']!==$ignore)$this->fail('MULTIPLE_PRIMARY_MEASUREMENTS');}
    private function validate(int $id): void
    {
        // Unit Type lock serializes Service rule writers. Canonical row locks
        // protect against Definition/Option lifecycle changes until commit.
        $measurements = $this->configs->listMeasurementRules($id);
        $attributes = $this->configs->listAttributeRules($id);
        usort($measurements, static fn (array $a, array $b): int =>
            $a['measurement_definition_id'] <=> $b['measurement_definition_id']);
        usort($attributes, static fn (array $a, array $b): int =>
            $a['attribute_definition_id'] <=> $b['attribute_definition_id']);
        $seen = [];
        $primaries = 0;
        foreach ($measurements as $rule) {
            $definitionId = $rule['measurement_definition_id'];
            if (isset($seen[$definitionId])) { $this->fail('DUPLICATE_CONFIGURATION_RULE'); }
            $seen[$definitionId] = true;
            $this->measurement($definitionId);
            $this->requirement($rule['requirement']);
            if ($rule['is_primary']) {
                if ($rule['requirement'] !== 'REQUIRED') { $this->fail('PRIMARY_MEASUREMENT_MUST_BE_REQUIRED'); }
                if (++$primaries > 1) { $this->fail('MULTIPLE_PRIMARY_MEASUREMENTS'); }
            }
        }
        $seen = [];
        foreach ($attributes as $rule) {
            $definitionId = $rule['attribute_definition_id'];
            if (isset($seen[$definitionId])) { $this->fail('DUPLICATE_CONFIGURATION_RULE'); }
            $seen[$definitionId] = true;
            $this->requirement($rule['requirement']);
            $this->attribute($definitionId);
        }
    }

    private function requirement(mixed $value): void
    {
        if (!in_array($value, ['REQUIRED', 'OPTIONAL'], true)) {
            $this->fail('CONFIGURATION_NOT_ACTIVATABLE');
        }
    }

    private function definitionId(mixed $id, string $error): int
    {
        if ((!is_int($id) && !is_string($id)) || preg_match('/^[0-9]+$/D', (string) $id) !== 1) {
            $this->fail($error);
        }
        $digits = ltrim((string) $id, '0');
        $max = (string) PHP_INT_MAX;
        if ($digits === '' || strlen($digits) > strlen($max)
            || (strlen($digits) === strlen($max) && strcmp($digits, $max) > 0)) {
            $this->fail($error);
        }
        return (int) $digits;
    }

    private function measurement(mixed $id): array
    {
        $definition = $this->measurements->findMeasurementDefinitionForUpdate(
            $this->definitionId($id, 'MEASUREMENT_DEFINITION_INACTIVE')
        );
        if ($definition === null || $definition['status'] !== 'active') {
            $this->fail('MEASUREMENT_DEFINITION_INACTIVE');
        }
        return $definition;
    }
 private function need(?array $v,string $c):array{if(!$v)$this->fail($c);return $v;}private function fail(string $c):never{throw new ValidationException([$c=>$c]);}
}
