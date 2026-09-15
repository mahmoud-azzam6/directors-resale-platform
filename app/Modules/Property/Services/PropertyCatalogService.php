<?php

declare(strict_types=1);

namespace App\Modules\Property\Services;

use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\AttributeDefinitionRepository;
use App\Modules\Property\Repositories\MeasurementDefinitionRepository;
use App\Modules\Property\Repositories\PropertyCatalogRepository;
use App\Modules\Property\Repositories\UnitTypeConfigurationRepository;
use PDOException;
use RuntimeException;

/**
 * Catalog business rules only. Authorization, HTTP, seed execution, and
 * Configuration lifecycle/version allocation remain outside this Service.
 */
final class PropertyCatalogService
{
    private const PROVENANCE = ['SYSTEM_ADMIN', 'SYSTEM_SEED'];
    private const ATTRIBUTE_TYPES = ['INTEGER', 'DECIMAL', 'BOOLEAN', 'TEXT', 'ENUM', 'DATE'];

    public function __construct(
        private PropertyCatalogRepository $catalogs,
        private MeasurementDefinitionRepository $measurements,
        private AttributeDefinitionRepository $attributes,
        private UnitTypeConfigurationRepository $configurations,
        private DatabaseConnectionInterface $database,
        private UlidGeneratorInterface $ulids
    ) {
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function createCategory(array $data, int|string|null $actorId = null): array
    {
        return $this->createCatalog($data, $actorId, fn (array $attributes): array => $this->catalogs->createCategory($attributes));
    }

    /** @return array<string,mixed>|null */
    public function findCategory(int|string $id): ?array { return $this->catalogs->findCategoryById($id); }
    /** @return list<array<string,mixed>> */
    public function listCategories(array $options = []): array { return $this->catalogs->listCategories($options); }

    /** @param array<string,mixed> $data @return array<string,mixed>|null */
    public function updateCategory(int|string $id, array $data, int|string|null $actorId = null): ?array
    {
        $this->assertNoImmutable($data);
        return $this->catalogs->updateCategory($id, $this->editable($data, ['name_ar', 'name_en', 'sort_order'], $actorId));
    }

    /** @return array<string,mixed> */
    public function deactivateCategory(int|string $id, int|string|null $actorId = null): array
    {
        return $this->database->transaction(function () use ($id, $actorId): array {
            $category = $this->required($this->catalogs->findCategoryForUpdate($id));
            $this->requireStatus($category, 'active');
            if ($this->catalogs->hasUnitTypesForCategory($category['id'], 'active')) {
                $this->fail('CATALOG_ITEM_REFERENCED');
            }

            return $this->required($this->catalogs->updateCategory($category['id'], $this->status('inactive', $actorId)));
        });
    }

    /** @return array<string,mixed> */
    public function reactivateCategory(int|string $id, int|string|null $actorId = null): array
    {
        return $this->changeCategoryStatus($id, 'inactive', 'active', $actorId);
    }

    public function deleteCategory(int|string $id): bool
    {
        return $this->database->transaction(function () use ($id): bool {
            $category = $this->required($this->catalogs->findCategoryForUpdate($id));
            $this->assertDeletable($category);
            if ($this->catalogs->hasUnitTypesForCategory($category['id'])) {
                $this->fail('CATALOG_ITEM_REFERENCED');
            }

            return $this->catalogs->deleteCategory($category['id']);
        });
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function createUnitType(array $data, int|string|null $actorId = null): array
    {
        $categoryId = $this->requiredId($data['property_category_id'] ?? null);
        return $this->database->transaction(function () use ($data, $actorId, $categoryId): array {
            $this->requireActive($this->required($this->catalogs->findCategoryForUpdate($categoryId)), 'PARENT_CATALOG_INACTIVE');
            return $this->createCatalog($data, $actorId, fn (array $attributes): array => $this->catalogs->createUnitType($attributes));
        });
    }

    /** @return array<string,mixed>|null */
    public function findUnitType(int|string $id): ?array { return $this->catalogs->findUnitTypeById($id); }
    /** @return list<array<string,mixed>> */
    public function listUnitTypes(array $options = []): array { return $this->catalogs->listUnitTypes($options); }

    /** @param array<string,mixed> $data @return array<string,mixed>|null */
    public function updateUnitType(int|string $id, array $data, int|string|null $actorId = null): ?array
    {
        $this->assertNoImmutable($data);
        if (! array_key_exists('property_category_id', $data)) {
            return $this->catalogs->updateUnitType($id, $this->editable($data, ['name_ar', 'name_en', 'sort_order'], $actorId));
        }
        $parentId = $this->requiredId($data['property_category_id']);
        return $this->database->transaction(function () use ($id, $data, $actorId, $parentId): ?array {
            $unit = $this->catalogs->findUnitTypeForUpdate($id);
            if ($unit === null) { return null; }
            $this->requireActive($this->required($this->catalogs->findCategoryForUpdate($parentId)), 'PARENT_CATALOG_INACTIVE');
            $changes = $this->editable($data, ['name_ar', 'name_en', 'sort_order'], $actorId);
            $changes['property_category_id'] = $parentId;
            return $this->catalogs->updateUnitType($unit['id'], $changes);
        });
    }

    /** @return array<string,mixed> */
    public function deactivateUnitType(int|string $id, int|string|null $actorId = null): array
    {
        return $this->changeUnitTypeStatus($id, 'active', 'inactive', $actorId, false);
    }

    /** @return array<string,mixed> */
    public function reactivateUnitType(int|string $id, int|string|null $actorId = null): array
    {
        return $this->changeUnitTypeStatus($id, 'inactive', 'active', $actorId, true);
    }

    public function deleteUnitType(int|string $id): bool
    {
        return $this->database->transaction(function () use ($id): bool {
            $unit = $this->required($this->catalogs->findUnitTypeForUpdate($id));
            $this->assertDeletable($unit);
            if ($this->configurations->hasConfigurationForUnitType($unit['id'])) { $this->fail('CATALOG_ITEM_REFERENCED'); }
            return $this->catalogs->deleteUnitType($unit['id']);
        });
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function createMeasurementDefinition(array $data, int|string|null $actorId = null): array
    {
        return $this->createCatalog($data, $actorId, function (array $attributes): array {
            if (! isset($attributes['default_unit_code'])) { $this->fail('CATALOG_ITEM_INACTIVE'); }
            return $this->measurements->createMeasurementDefinition($attributes);
        });
    }

    /** @return array<string,mixed>|null */
    public function findMeasurementDefinition(int|string $id): ?array { return $this->measurements->findMeasurementDefinitionById($id); }
    /** @return list<array<string,mixed>> */
    public function listMeasurementDefinitions(array $options = []): array { return $this->measurements->listMeasurementDefinitions($options); }

    /** @param array<string,mixed> $data @return array<string,mixed>|null */
    public function updateMeasurementDefinition(int|string $id, array $data, int|string|null $actorId = null): ?array
    {
        $this->assertNoImmutable($data, ['default_unit_code']);
        return $this->measurements->updateMeasurementDefinition($id, $this->editable($data, ['name_ar', 'name_en', 'sort_order'], $actorId));
    }

    /** @return array<string,mixed> */
    public function deactivateMeasurementDefinition(int|string $id, int|string|null $actorId = null): array
    {
        return $this->database->transaction(function () use ($id, $actorId): array {
            $definition = $this->required($this->measurements->findMeasurementDefinitionForUpdate($id));
            $this->requireStatus($definition, 'active');
            if ($this->configurations->hasActiveConfigurationWithMeasurementDefinition($definition['id'])) { $this->fail('CATALOG_ITEM_REFERENCED'); }
            return $this->required($this->measurements->updateMeasurementDefinition($definition['id'], $this->status('inactive', $actorId)));
        });
    }

    /** @return array<string,mixed> */
    public function reactivateMeasurementDefinition(int|string $id, int|string|null $actorId = null): array
    {
        return $this->changeMeasurementStatus($id, 'inactive', 'active', $actorId);
    }

    public function deleteMeasurementDefinition(int|string $id): bool
    {
        return $this->database->transaction(function () use ($id): bool {
            $definition = $this->required($this->measurements->findMeasurementDefinitionForUpdate($id));
            $this->assertDeletable($definition);
            if ($this->configurations->hasMeasurementRuleForDefinition($definition['id'])) { $this->fail('CATALOG_ITEM_REFERENCED'); }
            return $this->measurements->deleteMeasurementDefinition($definition['id']);
        });
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function createAttributeDefinition(array $data, int|string|null $actorId = null): array
    {
        return $this->createCatalog($data, $actorId, function (array $attributes): array {
            if (! isset($attributes['data_type']) || ! in_array($attributes['data_type'], self::ATTRIBUTE_TYPES, true)) { $this->fail('CATALOG_ITEM_INACTIVE'); }
            return $this->attributes->createAttributeDefinition($attributes);
        });
    }

    /** @return array<string,mixed>|null */
    public function findAttributeDefinition(int|string $id): ?array { return $this->attributes->findAttributeDefinitionById($id); }
    /** @return list<array<string,mixed>> */
    public function listAttributeDefinitions(array $options = []): array { return $this->attributes->listAttributeDefinitions($options); }

    /** @param array<string,mixed> $data @return array<string,mixed>|null */
    public function updateAttributeDefinition(int|string $id, array $data, int|string|null $actorId = null): ?array
    {
        $this->assertNoImmutable($data, ['data_type']);
        return $this->attributes->updateAttributeDefinition($id, $this->editable($data, ['name_ar', 'name_en', 'text_max_length', 'sort_order'], $actorId));
    }

    /** @return array<string,mixed> */
    public function deactivateAttributeDefinition(int|string $id, int|string|null $actorId = null): array
    {
        return $this->database->transaction(function () use ($id, $actorId): array {
            $definition = $this->required($this->attributes->findAttributeDefinitionForUpdate($id));
            $this->requireStatus($definition, 'active');
            if ($this->configurations->hasActiveConfigurationWithAttributeDefinition($definition['id'])) { $this->fail('CATALOG_ITEM_REFERENCED'); }
            return $this->required($this->attributes->updateAttributeDefinition($definition['id'], $this->status('inactive', $actorId)));
        });
    }

    /** @return array<string,mixed> */
    public function reactivateAttributeDefinition(int|string $id, int|string|null $actorId = null): array
    {
        return $this->changeAttributeStatus($id, 'inactive', 'active', $actorId);
    }

    public function deleteAttributeDefinition(int|string $id): bool
    {
        return $this->database->transaction(function () use ($id): bool {
            $definition = $this->required($this->attributes->findAttributeDefinitionForUpdate($id));
            $this->assertDeletable($definition);
            if ($this->attributes->hasAttributeOptionsForDefinition($definition['id']) || $this->configurations->hasAttributeRuleForDefinition($definition['id'])) { $this->fail('CATALOG_ITEM_REFERENCED'); }
            return $this->attributes->deleteAttributeDefinition($definition['id']);
        });
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function createAttributeOption(int|string $definitionId, array $data, int|string|null $actorId = null): array
    {
        return $this->database->transaction(function () use ($definitionId, $data, $actorId): array {
            $definition = $this->required($this->attributes->findAttributeDefinitionForUpdate($definitionId));
            if ($definition['data_type'] !== 'ENUM') { $this->fail('ATTRIBUTE_OPTIONS_REQUIRE_ENUM'); }
            $this->requireActive($definition, 'PARENT_CATALOG_INACTIVE');
            return $this->createCatalog($data, $actorId, fn (array $attributes): array => $this->attributes->createAttributeOption($definition['id'], $attributes));
        });
    }

    /** @return array<string,mixed>|null */
    public function findAttributeOption(int|string $id): ?array { return $this->attributes->findAttributeOptionById($id); }
    /** @return list<array<string,mixed>> */
    public function listAttributeOptions(int|string $definitionId, array $options = []): array { return $this->attributes->listAttributeOptions($definitionId, $options); }

    /** @param array<string,mixed> $data @return array<string,mixed>|null */
    public function updateAttributeOption(int|string $definitionId, int|string $optionId, array $data, int|string|null $actorId = null): ?array
    {
        $this->assertNoImmutable($data);
        return $this->attributes->updateAttributeOption($definitionId, $optionId, $this->editable($data, ['name_ar', 'name_en', 'sort_order'], $actorId));
    }

    /** @return array<string,mixed> */
    public function deactivateAttributeOption(int|string $definitionId, int|string $optionId, int|string|null $actorId = null): array
    {
        return $this->database->transaction(function () use ($definitionId, $optionId, $actorId): array {
            $definition = $this->required($this->attributes->findAttributeDefinitionForUpdate($definitionId));
            $option = $this->required($this->attributes->findAttributeOptionForUpdate($definition['id'], $optionId));
            $this->requireStatus($option, 'active');
            $activeOptions = $this->attributes->listActiveAttributeOptionsForUpdate($definition['id']);
            if ($this->configurations->hasActiveConfigurationWithAttributeDefinition($definition['id']) && count($activeOptions) <= 1) { $this->fail('ENUM_ACTIVE_OPTIONS_REQUIRED'); }
            return $this->required($this->attributes->updateAttributeOption($definition['id'], $option['id'], $this->status('inactive', $actorId)));
        });
    }

    /** @return array<string,mixed> */
    public function reactivateAttributeOption(int|string $definitionId, int|string $optionId, int|string|null $actorId = null): array
    {
        return $this->database->transaction(function () use ($definitionId, $optionId, $actorId): array {
            $definition = $this->required($this->attributes->findAttributeDefinitionForUpdate($definitionId));
            $this->requireActive($definition, 'PARENT_CATALOG_INACTIVE');
            $option = $this->required($this->attributes->findAttributeOptionForUpdate($definition['id'], $optionId));
            $this->requireStatus($option, 'inactive');
            return $this->required($this->attributes->updateAttributeOption($definition['id'], $option['id'], $this->status('active', $actorId)));
        });
    }

    public function deleteAttributeOption(int|string $definitionId, int|string $optionId): bool
    {
        return $this->database->transaction(function () use ($definitionId, $optionId): bool {
            $definition = $this->required($this->attributes->findAttributeDefinitionForUpdate($definitionId));
            $option = $this->required($this->attributes->findAttributeOptionForUpdate($definition['id'], $optionId));
            $this->assertDeletable($option);
            if ($this->configurations->hasActiveConfigurationWithAttributeDefinition($definition['id'])) {
                $active = $this->attributes->listActiveAttributeOptionsForUpdate($definition['id']);
                if ($option['status'] === 'active' && count($active) <= 1) { $this->fail('ENUM_ACTIVE_OPTIONS_REQUIRED'); }
            }
            return $this->attributes->deleteAttributeOption($definition['id'], $option['id']);
        });
    }

    /** @param array<string,mixed> $data @param callable(array<string,mixed>):array<string,mixed> $create */
    private function createCatalog(array $data, int|string|null $actorId, callable $create): array
    {
        $code = $this->normalizedCode($data['code'] ?? null);
        $attributes = $data;
        $attributes['code'] = $code;
        $attributes['ulid'] = $this->ulids->generate();
        $attributes['status'] = 'active';
        $attributes['provenance'] = $this->provenance($data['provenance'] ?? 'SYSTEM_ADMIN');
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at'], $attributes['created_by_user_id'], $attributes['updated_by_user_id']);
        $actor = $this->actor($actorId);
        $attributes['created_by_user_id'] = $actor;
        $attributes['updated_by_user_id'] = $actor;
        try { return $create($attributes); }
        catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { $this->fail('CATALOG_CODE_ALREADY_EXISTS'); }
            throw $exception;
        }
    }

    /** @param array<string,mixed> $data @param list<string> $allowed @return array<string,mixed> */
    private function editable(array $data, array $allowed, int|string|null $actorId): array
    {
        $changes = [];
        foreach ($allowed as $field) { if (array_key_exists($field, $data)) { $changes[$field] = $data[$field]; } }
        $changes['updated_by_user_id'] = $this->actor($actorId);
        return $changes;
    }

    /** @param array<string,mixed> $data @param list<string> $additional */
    private function assertNoImmutable(array $data, array $additional = []): void
    {
        foreach (array_merge(['id', 'ulid', 'code', 'provenance', 'created_by_user_id', 'updated_by_user_id', 'status', 'last_allocated_configuration_version'], $additional) as $field) {
            if (array_key_exists($field, $data)) { $this->fail('CATALOG_IDENTITY_IMMUTABLE'); }
        }
    }

    /** @param array<string,mixed> $record */
    private function requireStatus(array $record, string $status): void
    {
        if ($record['status'] !== $status) { $this->fail('CATALOG_ITEM_INACTIVE'); }
    }
    /** @param array<string,mixed> $record */
    private function requireActive(array $record, string $code): void
    {
        if ($record['status'] !== 'active') { $this->fail($code); }
    }
    /** @param array<string,mixed> $record */
    private function assertDeletable(array $record): void
    {
        if ($record['provenance'] === 'SYSTEM_SEED') { $this->fail('SYSTEM_SEED_DELETE_FORBIDDEN'); }
    }
    /** @return array<string,mixed> */
    private function changeCategoryStatus(int|string $id, string $from, string $to, int|string|null $actorId): array
    {
        return $this->database->transaction(function () use ($id, $from, $to, $actorId): array {
            $record = $this->required($this->catalogs->findCategoryForUpdate($id));
            $this->requireStatus($record, $from);
            return $this->required($this->catalogs->updateCategory($record['id'], $this->status($to, $actorId)));
        });
    }
    /** @return array<string,mixed> */
    private function changeUnitTypeStatus(int|string $id, string $from, string $to, int|string|null $actorId, bool $verifyParent): array
    {
        return $this->database->transaction(function () use ($id, $from, $to, $actorId, $verifyParent): array {
            $record = $this->required($this->catalogs->findUnitTypeForUpdate($id));
            $this->requireStatus($record, $from);
            if ($verifyParent) { $this->requireActive($this->required($this->catalogs->findCategoryForUpdate($record['property_category_id'])), 'PARENT_CATALOG_INACTIVE'); }
            return $this->required($this->catalogs->updateUnitType($record['id'], $this->status($to, $actorId)));
        });
    }
    /** @return array<string,mixed> */
    private function changeMeasurementStatus(int|string $id, string $from, string $to, int|string|null $actorId): array
    {
        return $this->database->transaction(function () use ($id, $from, $to, $actorId): array {
            $record = $this->required($this->measurements->findMeasurementDefinitionForUpdate($id));
            $this->requireStatus($record, $from);
            return $this->required($this->measurements->updateMeasurementDefinition($record['id'], $this->status($to, $actorId)));
        });
    }
    /** @return array<string,mixed> */
    private function changeAttributeStatus(int|string $id, string $from, string $to, int|string|null $actorId): array
    {
        return $this->database->transaction(function () use ($id, $from, $to, $actorId): array {
            $record = $this->required($this->attributes->findAttributeDefinitionForUpdate($id));
            $this->requireStatus($record, $from);
            return $this->required($this->attributes->updateAttributeDefinition($record['id'], $this->status($to, $actorId)));
        });
    }
    /** @return array<string,mixed> */
    private function status(string $status, int|string|null $actorId): array { return ['status' => $status, 'updated_by_user_id' => $this->actor($actorId)]; }
    private function normalizedCode(mixed $code): string
    {
        if (! is_string($code) || ($code = strtoupper(trim($code))) === '') { $this->fail('CATALOG_CODE_ALREADY_EXISTS'); }
        return $code;
    }
    private function provenance(mixed $provenance): string
    {
        if (! is_string($provenance) || ! in_array($provenance, self::PROVENANCE, true)) { $this->fail('CATALOG_ITEM_INACTIVE'); }
        return $provenance;
    }
    private function actor(int|string|null $actor): ?int
    {
        if ($actor === null) { return null; }
        return $this->requiredId($actor);
    }
    private function requiredId(mixed $value): int
    {
        if ((! is_int($value) && (! is_string($value) || ! ctype_digit($value))) || (int) $value <= 0) { $this->fail('CATALOG_ITEM_INACTIVE'); }
        return (int) $value;
    }
    /** @param array<string,mixed>|null $record @return array<string,mixed> */
    private function required(?array $record): array
    {
        if ($record === null) { $this->fail('CATALOG_ITEM_INACTIVE'); }
        return $record;
    }
    private function fail(string $code): never { throw new ValidationException([$code => $code]); }
}
