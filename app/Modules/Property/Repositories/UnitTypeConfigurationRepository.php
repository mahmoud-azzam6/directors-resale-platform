<?php

declare(strict_types=1);

namespace App\Modules\Property\Repositories;

use App\Core\Database\QueryBuilderInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Configuration persistence and explicit catalog-data assembly only.
 * Callers own transactions, lifecycle decisions, and version allocation.
 *
 * @phpstan-import-type MeasurementDefinitionRecord from MeasurementDefinitionRepository
 * @phpstan-import-type AttributeDefinitionRecord from AttributeDefinitionRepository
 * @phpstan-import-type AttributeOptionRecord from AttributeDefinitionRepository
 * @phpstan-type UnitTypeConfigurationRecord array{
 *     id: int,
 *     ulid: string,
 *     unit_type_id: int,
 *     version_number: int,
 *     status: string,
 *     provenance: string,
 *     created_by_user_id: int|null,
 *     updated_by_user_id: int|null,
 *     created_at: string,
 *     updated_at: string
 * }
 * @phpstan-type MeasurementRuleRecord array{
 *     id: int,
 *     configuration_version_id: int,
 *     measurement_definition_id: int,
 *     requirement: string,
 *     is_primary: bool,
 *     sort_order: int,
 *     created_by_user_id: int|null,
 *     updated_by_user_id: int|null,
 *     created_at: string,
 *     updated_at: string
 * }
 * @phpstan-type AttributeRuleRecord array{
 *     id: int,
 *     configuration_version_id: int,
 *     attribute_definition_id: int,
 *     requirement: string,
 *     sort_order: int,
 *     created_by_user_id: int|null,
 *     updated_by_user_id: int|null,
 *     created_at: string,
 *     updated_at: string
 * }
 * @phpstan-type UnitTypeConfigurationAggregate array{
 *     configuration: UnitTypeConfigurationRecord,
 *     measurement_rules: list<array{
 *         rule: MeasurementRuleRecord,
 *         definition: MeasurementDefinitionRecord
 *     }>,
 *     attribute_rules: list<array{
 *         rule: AttributeRuleRecord,
 *         definition: AttributeDefinitionRecord,
 *         options: list<AttributeOptionRecord>
 *     }>
 * }
 */
final class UnitTypeConfigurationRepository
{
    private const CONFIGURATION_COLUMNS = [
        'id', 'ulid', 'unit_type_id', 'version_number', 'status', 'provenance',
        'created_by_user_id', 'updated_by_user_id', 'created_at', 'updated_at',
    ];

    private const MEASUREMENT_RULE_COLUMNS = [
        'id', 'configuration_version_id', 'measurement_definition_id', 'requirement',
        'is_primary', 'sort_order', 'created_by_user_id', 'updated_by_user_id',
        'created_at', 'updated_at',
    ];

    private const ATTRIBUTE_RULE_COLUMNS = [
        'id', 'configuration_version_id', 'attribute_definition_id', 'requirement',
        'sort_order', 'created_by_user_id', 'updated_by_user_id', 'created_at', 'updated_at',
    ];

    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private MeasurementDefinitionRepository $measurementDefinitions,
        private AttributeDefinitionRepository $attributeDefinitions
    ) {
    }

    /** @return UnitTypeConfigurationRecord|null */
    public function findConfigurationById(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('unit_type_configuration_versions')
            ->select(self::CONFIGURATION_COLUMNS)->where('id', '=', $id)->first();

        return $row === null ? null : $this->mapConfiguration($row);
    }

    /** @return UnitTypeConfigurationRecord|null */
    public function findConfigurationByUlid(string $ulid): ?array
    {
        $row = $this->queryBuilder->table('unit_type_configuration_versions')
            ->select(self::CONFIGURATION_COLUMNS)->where('ulid', '=', $ulid)->first();

        return $row === null ? null : $this->mapConfiguration($row);
    }

    /** @return UnitTypeConfigurationRecord|null */
    public function findConfigurationByVersion(int|string $unitTypeId, int $versionNumber): ?array
    {
        $unitTypeId = $this->identifier($unitTypeId);
        $versionNumber = $this->identifier($versionNumber);
        $row = $this->queryBuilder->table('unit_type_configuration_versions')
            ->select(self::CONFIGURATION_COLUMNS)
            ->where('unit_type_id', '=', $unitTypeId)
            ->where('version_number', '=', $versionNumber)->first();

        return $row === null ? null : $this->mapConfiguration($row);
    }

    /** @return UnitTypeConfigurationRecord|null */
    public function findActiveConfigurationForUnitType(int|string $unitTypeId): ?array
    {
        $unitTypeId = $this->identifier($unitTypeId);
        $row = $this->queryBuilder->table('unit_type_configuration_versions')
            ->select(self::CONFIGURATION_COLUMNS)
            ->where('unit_type_id', '=', $unitTypeId)->where('status', '=', 'active')->first();

        return $row === null ? null : $this->mapConfiguration($row);
    }

    /** @return UnitTypeConfigurationRecord|null */
    public function findDraftConfigurationForUnitType(int|string $unitTypeId): ?array
    {
        $unitTypeId = $this->identifier($unitTypeId);
        $row = $this->queryBuilder->table('unit_type_configuration_versions')
            ->select(self::CONFIGURATION_COLUMNS)
            ->where('unit_type_id', '=', $unitTypeId)->where('status', '=', 'draft')->first();

        return $row === null ? null : $this->mapConfiguration($row);
    }

    /**
     * Caller owns the transaction and must acquire the Unit Type lock first.
     * @return UnitTypeConfigurationRecord|null
     */
    public function findDraftConfigurationForUpdate(int|string $unitTypeId): ?array
    {
        $unitTypeId = $this->identifier($unitTypeId);
        $row = $this->queryBuilder->table('unit_type_configuration_versions')
            ->select(self::CONFIGURATION_COLUMNS)
            ->where('unit_type_id', '=', $unitTypeId)->where('status', '=', 'draft')
            ->forUpdate()->first();

        return $row === null ? null : $this->mapConfiguration($row);
    }

    /**
     * @param array{status?: ?string, limit?: int, offset?: int} $options
     * @return list<UnitTypeConfigurationRecord>
     */
    public function listConfigurationsForUnitType(int|string $unitTypeId, array $options = []): array
    {
        $unitTypeId = $this->identifier($unitTypeId);
        $options = $this->listOptions($options);
        $query = $this->queryBuilder->table('unit_type_configuration_versions')
            ->select(self::CONFIGURATION_COLUMNS)->where('unit_type_id', '=', $unitTypeId);
        if ($options['status'] !== null) {
            $query->where('status', '=', $options['status']);
        }
        $rows = $query->orderBy('version_number', 'DESC')->orderBy('id', 'DESC')
            ->limit($options['limit'])->offset($options['offset'])->get();

        return array_map(fn (array $row): array => $this->mapConfiguration($row), $rows);
    }

    /**
     * Highest currently persisted version, or zero when none exists.
     * This neither allocates nor reserves a number and cannot guarantee never-reuse.
     */
    public function findLatestVersionNumberForUnitType(int|string $unitTypeId): int
    {
        $unitTypeId = $this->identifier($unitTypeId);
        $row = $this->queryBuilder->table('unit_type_configuration_versions')
            ->select(['version_number'])->where('unit_type_id', '=', $unitTypeId)
            ->orderBy('version_number', 'DESC')->orderBy('id', 'DESC')->first();

        return $row === null ? 0 : $this->storedInteger($row['version_number']);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return UnitTypeConfigurationRecord
     */
    public function createConfigurationVersion(array $attributes): array
    {
        $this->writeFields($attributes, [
            'ulid', 'unit_type_id', 'version_number', 'status', 'provenance',
            'created_by_user_id', 'updated_by_user_id',
        ], ['ulid', 'unit_type_id', 'version_number', 'status', 'provenance']);
        $id = $this->queryBuilder->table('unit_type_configuration_versions')->insert($attributes);
        $record = $this->findConfigurationById($id);
        if ($record === null) {
            throw new RuntimeException('Created Configuration Version could not be retrieved.');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $changes
     * @return UnitTypeConfigurationRecord|null
     */
    public function updateConfigurationVersion(int|string $id, array $changes): ?array
    {
        $id = $this->identifier($id);
        $this->writeFields($changes, ['status', 'updated_by_user_id']);
        if ($changes !== []) {
            $this->queryBuilder->table('unit_type_configuration_versions')
                ->where('id', '=', $id)->update($changes);
        }

        return $this->findConfigurationById($id);
    }

    /** Deletes only this row; referenced rules remain protected by database RESTRICT. */
    public function deleteConfigurationVersion(int|string $id): bool
    {
        $id = $this->identifier($id);

        return $this->queryBuilder->table('unit_type_configuration_versions')
            ->where('id', '=', $id)->delete() > 0;
    }

    /**
     * Caller owns the transaction and any preceding Unit Type lock.
     * @return UnitTypeConfigurationRecord|null
     */
    public function findConfigurationForUpdate(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('unit_type_configuration_versions')
            ->select(self::CONFIGURATION_COLUMNS)->where('id', '=', $id)
            ->forUpdate()->first();

        return $row === null ? null : $this->mapConfiguration($row);
    }

    /** @return MeasurementRuleRecord|null */
    public function findMeasurementRuleById(int|string $configurationId, int|string $ruleId): ?array
    {
        $configurationId = $this->identifier($configurationId);
        $ruleId = $this->identifier($ruleId);
        $row = $this->queryBuilder->table('unit_type_measurement_rules')
            ->select(self::MEASUREMENT_RULE_COLUMNS)
            ->where('configuration_version_id', '=', $configurationId)
            ->where('id', '=', $ruleId)->first();

        return $row === null ? null : $this->mapMeasurementRule($row);
    }

    /** @return MeasurementRuleRecord|null */
    public function findMeasurementRuleByDefinition(int|string $configurationId, int|string $definitionId): ?array
    {
        $configurationId = $this->identifier($configurationId);
        $definitionId = $this->identifier($definitionId);
        $row = $this->queryBuilder->table('unit_type_measurement_rules')
            ->select(self::MEASUREMENT_RULE_COLUMNS)
            ->where('configuration_version_id', '=', $configurationId)
            ->where('measurement_definition_id', '=', $definitionId)->first();

        return $row === null ? null : $this->mapMeasurementRule($row);
    }

    /** @return list<MeasurementRuleRecord> */
    public function listMeasurementRules(int|string $configurationId): array
    {
        $configurationId = $this->identifier($configurationId);
        $rows = $this->queryBuilder->table('unit_type_measurement_rules')
            ->select(self::MEASUREMENT_RULE_COLUMNS)
            ->where('configuration_version_id', '=', $configurationId)
            ->orderBy('sort_order')->orderBy('id')->get();

        return array_map(fn (array $row): array => $this->mapMeasurementRule($row), $rows);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return MeasurementRuleRecord
     */
    public function createMeasurementRule(int|string $configurationId, array $attributes): array
    {
        $configurationId = $this->identifier($configurationId);
        $this->writeFields($attributes, [
            'measurement_definition_id', 'requirement', 'is_primary', 'sort_order',
            'created_by_user_id', 'updated_by_user_id',
        ], ['measurement_definition_id', 'requirement', 'is_primary']);
        $attributes['configuration_version_id'] = $configurationId;
        $id = $this->queryBuilder->table('unit_type_measurement_rules')->insert($attributes);
        $record = $this->findMeasurementRuleById($configurationId, $id);
        if ($record === null) {
            throw new RuntimeException('Created Measurement Rule could not be retrieved.');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $changes
     * @return MeasurementRuleRecord|null
     */
    public function updateMeasurementRule(
        int|string $configurationId,
        int|string $ruleId,
        array $changes
    ): ?array {
        $configurationId = $this->identifier($configurationId);
        $ruleId = $this->identifier($ruleId);
        $this->writeFields($changes, ['requirement', 'is_primary', 'sort_order', 'updated_by_user_id']);
        if ($changes !== []) {
            $this->queryBuilder->table('unit_type_measurement_rules')
                ->where('configuration_version_id', '=', $configurationId)
                ->where('id', '=', $ruleId)->update($changes);
        }

        return $this->findMeasurementRuleById($configurationId, $ruleId);
    }

    public function deleteMeasurementRule(int|string $configurationId, int|string $ruleId): bool
    {
        $configurationId = $this->identifier($configurationId);
        $ruleId = $this->identifier($ruleId);

        return $this->queryBuilder->table('unit_type_measurement_rules')
            ->where('configuration_version_id', '=', $configurationId)
            ->where('id', '=', $ruleId)->delete() > 0;
    }

    /** @return AttributeRuleRecord|null */
    public function findAttributeRuleById(int|string $configurationId, int|string $ruleId): ?array
    {
        $configurationId = $this->identifier($configurationId);
        $ruleId = $this->identifier($ruleId);
        $row = $this->queryBuilder->table('unit_type_attribute_rules')
            ->select(self::ATTRIBUTE_RULE_COLUMNS)
            ->where('configuration_version_id', '=', $configurationId)
            ->where('id', '=', $ruleId)->first();

        return $row === null ? null : $this->mapAttributeRule($row);
    }

    /** @return AttributeRuleRecord|null */
    public function findAttributeRuleByDefinition(int|string $configurationId, int|string $definitionId): ?array
    {
        $configurationId = $this->identifier($configurationId);
        $definitionId = $this->identifier($definitionId);
        $row = $this->queryBuilder->table('unit_type_attribute_rules')
            ->select(self::ATTRIBUTE_RULE_COLUMNS)
            ->where('configuration_version_id', '=', $configurationId)
            ->where('attribute_definition_id', '=', $definitionId)->first();

        return $row === null ? null : $this->mapAttributeRule($row);
    }

    /** @return list<AttributeRuleRecord> */
    public function listAttributeRules(int|string $configurationId): array
    {
        $configurationId = $this->identifier($configurationId);
        $rows = $this->queryBuilder->table('unit_type_attribute_rules')
            ->select(self::ATTRIBUTE_RULE_COLUMNS)
            ->where('configuration_version_id', '=', $configurationId)
            ->orderBy('sort_order')->orderBy('id')->get();

        return array_map(fn (array $row): array => $this->mapAttributeRule($row), $rows);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return AttributeRuleRecord
     */
    public function createAttributeRule(int|string $configurationId, array $attributes): array
    {
        $configurationId = $this->identifier($configurationId);
        $this->writeFields($attributes, [
            'attribute_definition_id', 'requirement', 'sort_order',
            'created_by_user_id', 'updated_by_user_id',
        ], ['attribute_definition_id', 'requirement']);
        $attributes['configuration_version_id'] = $configurationId;
        $id = $this->queryBuilder->table('unit_type_attribute_rules')->insert($attributes);
        $record = $this->findAttributeRuleById($configurationId, $id);
        if ($record === null) {
            throw new RuntimeException('Created Attribute Rule could not be retrieved.');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $changes
     * @return AttributeRuleRecord|null
     */
    public function updateAttributeRule(
        int|string $configurationId,
        int|string $ruleId,
        array $changes
    ): ?array {
        $configurationId = $this->identifier($configurationId);
        $ruleId = $this->identifier($ruleId);
        $this->writeFields($changes, ['requirement', 'sort_order', 'updated_by_user_id']);
        if ($changes !== []) {
            $this->queryBuilder->table('unit_type_attribute_rules')
                ->where('configuration_version_id', '=', $configurationId)
                ->where('id', '=', $ruleId)->update($changes);
        }

        return $this->findAttributeRuleById($configurationId, $ruleId);
    }

    public function deleteAttributeRule(int|string $configurationId, int|string $ruleId): bool
    {
        $configurationId = $this->identifier($configurationId);
        $ruleId = $this->identifier($ruleId);

        return $this->queryBuilder->table('unit_type_attribute_rules')
            ->where('configuration_version_id', '=', $configurationId)
            ->where('id', '=', $ruleId)->delete() > 0;
    }

    /**
     * Current persisted metadata, including inactive Definitions and Options.
     * Caller supplies an appropriate transaction when a consistent snapshot is needed.
     * @return UnitTypeConfigurationAggregate|null
     */
    public function findConfigurationAggregateById(int|string $id): ?array
    {
        $configuration = $this->findConfigurationById($id);
        if ($configuration === null) {
            return null;
        }

        $measurementRules = $this->listMeasurementRules($configuration['id']);
        $attributeRules = $this->listAttributeRules($configuration['id']);
        $measurementDefinitions = [];
        $attributeDefinitions = [];
        $optionsByDefinition = [];

        if ($measurementRules !== []) {
            $measurementDefinitions = array_column(
                $this->measurementDefinitions->findMeasurementDefinitionsByIds(
                    array_column($measurementRules, 'measurement_definition_id')
                ),
                null,
                'id'
            );
        }
        if ($attributeRules !== []) {
            $definitionIds = array_column($attributeRules, 'attribute_definition_id');
            $attributeDefinitions = array_column(
                $this->attributeDefinitions->findAttributeDefinitionsByIds($definitionIds),
                null,
                'id'
            );
            foreach ($this->attributeDefinitions->listAttributeOptionsForDefinitions($definitionIds) as $option) {
                $optionsByDefinition[$option['attribute_definition_id']][] = $option;
            }
        }

        $aggregate = [
            'configuration' => $configuration,
            'measurement_rules' => [],
            'attribute_rules' => [],
        ];
        foreach ($measurementRules as $rule) {
            $definitionId = $rule['measurement_definition_id'];
            if (! isset($measurementDefinitions[$definitionId])) {
                throw new RuntimeException(
                    'Configuration persistence inconsistency: missing Measurement Definition ' . $definitionId . '.'
                );
            }
            $aggregate['measurement_rules'][] = [
                'rule' => $rule,
                'definition' => $measurementDefinitions[$definitionId],
            ];
        }
        foreach ($attributeRules as $rule) {
            $definitionId = $rule['attribute_definition_id'];
            if (! isset($attributeDefinitions[$definitionId])) {
                throw new RuntimeException(
                    'Configuration persistence inconsistency: missing Attribute Definition ' . $definitionId . '.'
                );
            }
            $aggregate['attribute_rules'][] = [
                'rule' => $rule,
                'definition' => $attributeDefinitions[$definitionId],
                'options' => $optionsByDefinition[$definitionId] ?? [],
            ];
        }

        return $aggregate;
    }

    /**
     * @param array<string, mixed> $row
     * @return UnitTypeConfigurationRecord
     */
    private function mapConfiguration(array $row): array
    {
        return [
            'id' => $this->storedInteger($row['id']),
            'ulid' => (string) $row['ulid'],
            'unit_type_id' => $this->storedInteger($row['unit_type_id']),
            'version_number' => $this->storedInteger($row['version_number']),
            'status' => (string) $row['status'],
            'provenance' => (string) $row['provenance'],
            'created_by_user_id' => $row['created_by_user_id'] === null ? null : $this->storedInteger($row['created_by_user_id']),
            'updated_by_user_id' => $row['updated_by_user_id'] === null ? null : $this->storedInteger($row['updated_by_user_id']),
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return MeasurementRuleRecord
     */
    private function mapMeasurementRule(array $row): array
    {
        return [
            'id' => $this->storedInteger($row['id']),
            'configuration_version_id' => $this->storedInteger($row['configuration_version_id']),
            'measurement_definition_id' => $this->storedInteger($row['measurement_definition_id']),
            'requirement' => (string) $row['requirement'],
            'is_primary' => $this->storedBoolean($row['is_primary']),
            'sort_order' => $this->storedInteger($row['sort_order']),
            'created_by_user_id' => $row['created_by_user_id'] === null ? null : $this->storedInteger($row['created_by_user_id']),
            'updated_by_user_id' => $row['updated_by_user_id'] === null ? null : $this->storedInteger($row['updated_by_user_id']),
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return AttributeRuleRecord
     */
    private function mapAttributeRule(array $row): array
    {
        return [
            'id' => $this->storedInteger($row['id']),
            'configuration_version_id' => $this->storedInteger($row['configuration_version_id']),
            'attribute_definition_id' => $this->storedInteger($row['attribute_definition_id']),
            'requirement' => (string) $row['requirement'],
            'sort_order' => $this->storedInteger($row['sort_order']),
            'created_by_user_id' => $row['created_by_user_id'] === null ? null : $this->storedInteger($row['created_by_user_id']),
            'updated_by_user_id' => $row['updated_by_user_id'] === null ? null : $this->storedInteger($row['updated_by_user_id']),
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    /**
     * Validate all options before touching the stateful QueryBuilder.
     * @param array<string, mixed> $options
     * @return array{status: string|null, limit: int, offset: int}
     */
    private function listOptions(array $options): array
    {
        foreach (array_keys($options) as $key) {
            if (! in_array($key, ['status', 'limit', 'offset'], true)) {
                throw new InvalidArgumentException('Unknown configuration list option.');
            }
        }
        $options += ['status' => null, 'limit' => 50, 'offset' => 0];
        if ($options['status'] !== null && ! is_string($options['status'])) {
            throw new InvalidArgumentException('status must be a string or null.');
        }
        if (! is_int($options['limit']) || $options['limit'] < 1 || $options['limit'] > 200) {
            throw new InvalidArgumentException('limit must be an integer between 1 and 200.');
        }
        if (! is_int($options['offset']) || $options['offset'] < 0) {
            throw new InvalidArgumentException('offset must be a nonnegative integer.');
        }

        return $options;
    }

    /**
     * Persistence input validation only; domain eligibility remains with Services.
     * @param array<string, mixed> $data
     * @param list<string> $allowed
     * @param list<string> $required
     */
    private function writeFields(array $data, array $allowed, array $required = []): void
    {
        foreach ($data as $field => $value) {
            if (! in_array($field, $allowed, true)) {
                throw new InvalidArgumentException('Unknown or read-only write field.');
            }
            if ($value !== null && ! is_scalar($value)) {
                throw new InvalidArgumentException('Write values must be scalar or null.');
            }
        }
        foreach ($required as $field) {
            if (! array_key_exists($field, $data)) {
                throw new InvalidArgumentException('Missing required write field: ' . $field);
            }
        }
    }

    /** Validate numeric identity input without silently overflowing PHP integers. */
    private function identifier(mixed $id): int
    {
        if ((! is_int($id) && ! is_string($id)) || preg_match('/^[0-9]+$/D', (string) $id) !== 1) {
            throw new InvalidArgumentException('ID must be a positive integer or decimal integer string.');
        }
        $digits = ltrim((string) $id, '0');
        $maximum = (string) PHP_INT_MAX;
        if ($digits === '' || strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            throw new InvalidArgumentException('ID is outside the supported positive integer range.');
        }

        return (int) $digits;
    }

    private function storedInteger(mixed $value): int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if ($integer === false) {
            throw new RuntimeException('Persisted integer is outside the supported PHP integer range.');
        }

        return $integer;
    }

    private function storedBoolean(mixed $value): bool
    {
        if (in_array($value, [0, '0'], true)) {
            return false;
        }
        if (in_array($value, [1, '1'], true)) {
            return true;
        }

        throw new RuntimeException('Persisted is_primary is not a valid boolean representation.');
    }
}
