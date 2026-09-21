<?php

declare(strict_types=1);

namespace App\Modules\Property\Services;

use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\AttributeDefinitionRepository;
use App\Modules\Property\Repositories\MeasurementDefinitionRepository;
use App\Modules\Property\Repositories\PropertyCatalogRepository;
use App\Modules\Property\Repositories\UnitTypeConfigurationRepository;

/**
 * Read-only, frontend-neutral projection for new Property entry. Authorization,
 * HTTP mapping, and Property value persistence remain outside this service.
 */
final class PropertyFormProjectionService
{
    public function __construct(
        private PropertyCatalogRepository $catalogs,
        private UnitTypeConfigurationRepository $configurations,
        private MeasurementDefinitionRepository $measurements,
        private AttributeDefinitionRepository $attributes
    ) {
    }

    /** @return array<string, mixed>|null */
    public function projectForUnitType(int|string $unitTypeId): ?array
    {
        $unitType = $this->catalogs->findUnitTypeById($unitTypeId);
        if ($unitType === null) {
            return null;
        }
        if ($unitType['status'] !== 'active') {
            $this->fail('CATALOG_ITEM_INACTIVE');
        }

        $category = $this->catalogs->findCategoryById($unitType['property_category_id']);
        if ($category === null) {
            $this->fail('CONFIGURATION_NOT_ACTIVATABLE');
        }
        $configuration = $this->configurations->findActiveConfigurationForUnitType($unitType['id']);
        if ($configuration === null) {
            $this->fail('NO_ACTIVE_CONFIGURATION');
        }

        $measurementRules = $this->configurations->listMeasurementRules($configuration['id']);
        $attributeRules = $this->configurations->listAttributeRules($configuration['id']);
        $measurementDefinitions = $this->definitionsById(
            $this->measurements->findMeasurementDefinitionsByIds(array_column($measurementRules, 'measurement_definition_id'))
        );
        $attributeDefinitions = $this->definitionsById(
            $this->attributes->findAttributeDefinitionsByIds(array_column($attributeRules, 'attribute_definition_id'))
        );
        $enumDefinitionIds = [];
        foreach ($attributeRules as $rule) {
            $definition = $attributeDefinitions[$rule['attribute_definition_id']] ?? null;
            if ($definition === null) {
                $this->fail('CONFIGURATION_NOT_ACTIVATABLE');
            }
            if ($definition['data_type'] === 'ENUM') {
                $enumDefinitionIds[] = $definition['id'];
            }
        }
        $optionsByDefinition = [];
        foreach ($this->attributes->listActiveAttributeOptionsForDefinitions($enumDefinitionIds) as $option) {
            $optionsByDefinition[$option['attribute_definition_id']][] = $option;
        }

        $seenMeasurements = [];
        $measurementFields = [];
        foreach ($measurementRules as $rule) {
            $definitionId = $rule['measurement_definition_id'];
            if (isset($seenMeasurements[$definitionId]) || ! isset($measurementDefinitions[$definitionId])) {
                $this->fail('CONFIGURATION_NOT_ACTIVATABLE');
            }
            $seenMeasurements[$definitionId] = true;
            $definition = $measurementDefinitions[$definitionId];
            $measurementFields[] = [
                'definition_id' => $definition['id'],
                'code' => $definition['code'],
                'name_en' => $definition['name_en'],
                'name_ar' => $definition['name_ar'],
                'unit' => $definition['default_unit_code'],
                'required' => $rule['requirement'] === 'REQUIRED',
                'primary' => $rule['is_primary'],
                'sort_order' => $rule['sort_order'],
            ];
        }

        $seenAttributes = [];
        $attributeFields = [];
        foreach ($attributeRules as $rule) {
            $definitionId = $rule['attribute_definition_id'];
            if (isset($seenAttributes[$definitionId]) || ! isset($attributeDefinitions[$definitionId])) {
                $this->fail('CONFIGURATION_NOT_ACTIVATABLE');
            }
            $seenAttributes[$definitionId] = true;
            $definition = $attributeDefinitions[$definitionId];
            $options = $optionsByDefinition[$definitionId] ?? [];
            if ($definition['data_type'] === 'ENUM' && $options === []) {
                $this->fail('ENUM_HAS_NO_ACTIVE_OPTIONS');
            }
            $attributeFields[] = [
                'definition_id' => $definition['id'],
                'code' => $definition['code'],
                'name_en' => $definition['name_en'],
                'name_ar' => $definition['name_ar'],
                'data_type' => $definition['data_type'],
                'required' => $rule['requirement'] === 'REQUIRED',
                'sort_order' => $rule['sort_order'],
                'options' => array_map(static fn (array $option): array => [
                    'id' => $option['id'],
                    'code' => $option['code'],
                    'name_en' => $option['name_en'],
                    'name_ar' => $option['name_ar'],
                    'sort_order' => $option['sort_order'],
                ], $definition['data_type'] === 'ENUM' ? $options : []),
            ];
        }

        return [
            'unit_type' => [
                'id' => $unitType['id'],
                'code' => $unitType['code'],
                'name_en' => $unitType['name_en'],
                'name_ar' => $unitType['name_ar'],
                'category' => [
                    'id' => $category['id'],
                    'code' => $category['code'],
                    'name_en' => $category['name_en'],
                    'name_ar' => $category['name_ar'],
                ],
            ],
            'configuration' => [
                'id' => $configuration['id'],
                'version_number' => $configuration['version_number'],
                'status' => $configuration['status'],
                'provenance' => $configuration['provenance'],
            ],
            'measurements' => $measurementFields,
            'attributes' => $attributeFields,
        ];
    }

    /** @param list<array<string, mixed>> $definitions @return array<int, array<string, mixed>> */
    private function definitionsById(array $definitions): array
    {
        return array_column($definitions, null, 'id');
    }

    private function fail(string $code): never
    {
        throw new ValidationException([$code => $code]);
    }
}
