<?php

declare(strict_types=1);

namespace App\Modules\Property\Repositories;

use App\Core\Database\DatabaseConnectionInterface;
use App\Core\Database\QueryBuilderInterface;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * Canonical catalog persistence only. No authorization, lifecycle, or transaction ownership.
 * Read models expose persisted fields without joins or code normalization.
 *
 * @phpstan-type AttributeDefinitionRecord array{
 *     id: int,
 *     ulid: string,
 *     code: string,
 *     name_ar: string,
 *     name_en: string,
 *     data_type: string,
 *     text_max_length: int|null,
 *     status: string,
 *     provenance: string,
 *     sort_order: int,
 *     created_by_user_id: int|null,
 *     updated_by_user_id: int|null,
 *     created_at: string,
 *     updated_at: string,
 * }
 * @phpstan-type AttributeOptionRecord array{
 *     id: int,
 *     ulid: string,
 *     attribute_definition_id: int,
 *     code: string,
 *     name_ar: string,
 *     name_en: string,
 *     status: string,
 *     provenance: string,
 *     sort_order: int,
 *     created_by_user_id: int|null,
 *     updated_by_user_id: int|null,
 *     created_at: string,
 *     updated_at: string,
 * }
 */
final class AttributeDefinitionRepository
{
    private const ATTRIBUTE_DEFINITION_COLUMNS = ['id', 'ulid', 'code', 'name_ar', 'name_en', 'data_type', 'text_max_length', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id', 'created_at', 'updated_at'];

    private const ATTRIBUTE_OPTION_COLUMNS = ['id', 'ulid', 'attribute_definition_id', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id', 'created_at', 'updated_at'];

    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private DatabaseConnectionInterface $database
    ) {
    }

    /** @return AttributeDefinitionRecord|null */
    public function findAttributeDefinitionById(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('attribute_definitions')->select(self::ATTRIBUTE_DEFINITION_COLUMNS)
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : $this->mapAttributeDefinition($row);
    }

    /** @return AttributeDefinitionRecord|null */
    public function findAttributeDefinitionByUlid(string $ulid): ?array
    {
        $row = $this->queryBuilder->table('attribute_definitions')->select(self::ATTRIBUTE_DEFINITION_COLUMNS)
            ->where('ulid', '=', $ulid)
            ->first();

        return $row === null ? null : $this->mapAttributeDefinition($row);
    }

    /** @return AttributeDefinitionRecord|null */
    public function findAttributeDefinitionByCode(string $code): ?array
    {
        $row = $this->queryBuilder->table('attribute_definitions')->select(self::ATTRIBUTE_DEFINITION_COLUMNS)
            ->where('code', '=', $code)
            ->first();

        return $row === null ? null : $this->mapAttributeDefinition($row);
    }

    /** Advisory only; uniqueness remains enforced by the database. */
    public function attributeDefinitionExistsByCode(string $code): bool
    {
        return $this->findAttributeDefinitionByCode($code) !== null;
    }

    /**
     * @param array{status?: ?string, search?: string, limit?: int, offset?: int, data_type?: string} $options
     * @return list<AttributeDefinitionRecord>
     */
    public function listAttributeDefinitions(array $options = []): array
    {
        $options = $this->listOptions($options, ['data_type']);
        if (array_key_exists('data_type', $options) && ! is_string($options['data_type'])) {
            throw new InvalidArgumentException('data_type must be a string.');
        }

        if ($options['search'] === '') {
            $query = $this->queryBuilder->table('attribute_definitions')->select(self::ATTRIBUTE_DEFINITION_COLUMNS);
            if ($options['status'] !== null) {
                $query->where('status', '=', $options['status']);
            }
            if (array_key_exists('data_type', $options)) {
                $query->where('data_type', '=', $options['data_type']);
            }
            $rows = $query->orderBy('sort_order')->orderBy('id')
                ->limit($options['limit'])->offset($options['offset'])->get();
        } else {
            // QueryBuilder cannot group OR predicates under the status/parent filters.
            $sql = 'SELECT `id`, `ulid`, `code`, `name_ar`, `name_en`, `data_type`, `text_max_length`, `status`, `provenance`, `sort_order`, `created_by_user_id`, `updated_by_user_id`, `created_at`, `updated_at`'
                . ' FROM `attribute_definitions` WHERE 1 = 1';
            if ($options['status'] !== null) {
                $sql .= ' AND `status` = :status';
            }
            if (array_key_exists('data_type', $options)) {
                $sql .= ' AND `data_type` = :data_type';
            }
            $sql .= " AND (`code` LIKE :search_code ESCAPE '!'"
                . " OR `name_ar` LIKE :search_ar ESCAPE '!'"
                . " OR `name_en` LIKE :search_en ESCAPE '!')"
                . ' ORDER BY `sort_order` ASC, `id` ASC LIMIT :limit OFFSET :offset';
            $statement = $this->database->connection()->prepare($sql);
            if ($statement === false) {
                throw new RuntimeException('Unable to prepare catalog search.');
            }
            if ($options['status'] !== null) {
                $statement->bindValue(':status', $options['status'], PDO::PARAM_STR);
            }
            if (array_key_exists('data_type', $options)) {
                $statement->bindValue(':data_type', $options['data_type'], PDO::PARAM_STR);
            }
            $pattern = '%' . strtr($options['search'], ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            foreach ([':search_code', ':search_ar', ':search_en'] as $parameter) {
                $statement->bindValue($parameter, $pattern, PDO::PARAM_STR);
            }
            $statement->bindValue(':limit', $options['limit'], PDO::PARAM_INT);
            $statement->bindValue(':offset', $options['offset'], PDO::PARAM_INT);
            $statement->execute();
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        return array_map(fn (array $row): array => $this->mapAttributeDefinition($row), $rows);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return AttributeDefinitionRecord
     */
    public function createAttributeDefinition(array $attributes): array
    {
        $this->writeFields($attributes, ['ulid', 'code', 'name_ar', 'name_en', 'data_type', 'text_max_length', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id'], ['ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'data_type']);
        $id = $this->queryBuilder->table('attribute_definitions')->insert($attributes);
        $record = $this->findAttributeDefinitionById($id);
        if ($record === null) {
            throw new RuntimeException('Created AttributeDefinition could not be retrieved.');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $changes
     * @return AttributeDefinitionRecord|null
     */
    public function updateAttributeDefinition(int|string $id, array $changes): ?array
    {
        $id = $this->identifier($id);
        $this->writeFields($changes, ['name_ar', 'name_en', 'status', 'sort_order', 'updated_by_user_id', 'data_type', 'text_max_length']);
        if ($changes !== []) {
            $this->queryBuilder->table('attribute_definitions')->where('id', '=', $id)
                ->update($changes);
        }

        return $this->findAttributeDefinitionById($id);
    }

    public function deleteAttributeDefinition(int|string $id): bool
    {
        $id = $this->identifier($id);

        return $this->queryBuilder->table('attribute_definitions')->where('id', '=', $id)
            ->delete() > 0;
    }

    /** Caller owns the transaction. @return AttributeDefinitionRecord|null */
    public function findAttributeDefinitionForUpdate(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('attribute_definitions')->select(self::ATTRIBUTE_DEFINITION_COLUMNS)
            ->where('id', '=', $id)->forUpdate()->first();

        return $row === null ? null : $this->mapAttributeDefinition($row);
    }

    public function hasAttributeOptionsForDefinition(int|string $definitionId): bool
    {
        $definitionId = $this->identifier($definitionId);

        return $this->queryBuilder->table('attribute_options')->select(['id'])
            ->where('attribute_definition_id', '=', $definitionId)->first() !== null;
    }

    /**
     * @param list<int|string> $ids
     * @return list<AttributeDefinitionRecord>
     */
    public function findAttributeDefinitionsByIds(array $ids): array
    {
        $ids = array_map(fn (mixed $id): int => $this->identifier($id), array_values($ids));
        if ($ids === []) {
            return [];
        }
        $rows = $this->queryBuilder->table('attribute_definitions')->select(self::ATTRIBUTE_DEFINITION_COLUMNS)
            ->whereIn('id', array_values(array_unique($ids)))
            ->orderBy('id')->get();

        return array_map(fn (array $row): array => $this->mapAttributeDefinition($row), $rows);
    }

    /**
     * @param array<string, mixed> $row
     * @return AttributeDefinitionRecord
     */
    private function mapAttributeDefinition(array $row): array
    {
        return [
            'id' => $this->storedInteger($row['id']),
            'ulid' => (string) $row['ulid'],
            'code' => (string) $row['code'],
            'name_ar' => (string) $row['name_ar'],
            'name_en' => (string) $row['name_en'],
            'data_type' => (string) $row['data_type'],
            'text_max_length' => $row['text_max_length'] === null ? null : $this->storedInteger($row['text_max_length']),
            'status' => (string) $row['status'],
            'provenance' => (string) $row['provenance'],
            'sort_order' => $this->storedInteger($row['sort_order']),
            'created_by_user_id' => $row['created_by_user_id'] === null ? null : $this->storedInteger($row['created_by_user_id']),
            'updated_by_user_id' => $row['updated_by_user_id'] === null ? null : $this->storedInteger($row['updated_by_user_id']),
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    /** @return AttributeOptionRecord|null */
    public function findAttributeOptionById(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('attribute_options')->select(self::ATTRIBUTE_OPTION_COLUMNS)
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : $this->mapAttributeOption($row);
    }

    /** @return AttributeOptionRecord|null */
    public function findAttributeOptionByUlid(string $ulid): ?array
    {
        $row = $this->queryBuilder->table('attribute_options')->select(self::ATTRIBUTE_OPTION_COLUMNS)
            ->where('ulid', '=', $ulid)
            ->first();

        return $row === null ? null : $this->mapAttributeOption($row);
    }

    /** @return AttributeOptionRecord|null */
    public function findAttributeOptionByCode(int|string $definitionId, string $code): ?array
    {
        $definitionId = $this->identifier($definitionId);
        $row = $this->queryBuilder->table('attribute_options')->select(self::ATTRIBUTE_OPTION_COLUMNS)
            ->where('code', '=', $code)
            ->where('attribute_definition_id', '=', $definitionId)
            ->first();

        return $row === null ? null : $this->mapAttributeOption($row);
    }

    /** Advisory only; uniqueness remains enforced by the database. */
    public function attributeOptionExistsByCode(int|string $definitionId, string $code): bool
    {
        return $this->findAttributeOptionByCode($definitionId, $code) !== null;
    }

    /**
     * @param array{status?: ?string, search?: string, limit?: int, offset?: int} $options
     * @return list<AttributeOptionRecord>
     */
    public function listAttributeOptions(int|string $definitionId, array $options = []): array
    {
        $definitionId = $this->identifier($definitionId);
        $options = $this->listOptions($options, []);
        $options['attribute_definition_id'] = $definitionId;

        if ($options['search'] === '') {
            $query = $this->queryBuilder->table('attribute_options')->select(self::ATTRIBUTE_OPTION_COLUMNS);
            if ($options['status'] !== null) {
                $query->where('status', '=', $options['status']);
            }
            if (array_key_exists('attribute_definition_id', $options)) {
                $query->where('attribute_definition_id', '=', $options['attribute_definition_id']);
            }
            $rows = $query->orderBy('sort_order')->orderBy('id')
                ->limit($options['limit'])->offset($options['offset'])->get();
        } else {
            // QueryBuilder cannot group OR predicates under the status/parent filters.
            $sql = 'SELECT `id`, `ulid`, `attribute_definition_id`, `code`, `name_ar`, `name_en`, `status`, `provenance`, `sort_order`, `created_by_user_id`, `updated_by_user_id`, `created_at`, `updated_at`'
                . ' FROM `attribute_options` WHERE 1 = 1';
            if ($options['status'] !== null) {
                $sql .= ' AND `status` = :status';
            }
            if (array_key_exists('attribute_definition_id', $options)) {
                $sql .= ' AND `attribute_definition_id` = :attribute_definition_id';
            }
            $sql .= " AND (`code` LIKE :search_code ESCAPE '!'"
                . " OR `name_ar` LIKE :search_ar ESCAPE '!'"
                . " OR `name_en` LIKE :search_en ESCAPE '!')"
                . ' ORDER BY `sort_order` ASC, `id` ASC LIMIT :limit OFFSET :offset';
            $statement = $this->database->connection()->prepare($sql);
            if ($statement === false) {
                throw new RuntimeException('Unable to prepare catalog search.');
            }
            if ($options['status'] !== null) {
                $statement->bindValue(':status', $options['status'], PDO::PARAM_STR);
            }
            if (array_key_exists('attribute_definition_id', $options)) {
                $statement->bindValue(':attribute_definition_id', $options['attribute_definition_id'], PDO::PARAM_INT);
            }
            $pattern = '%' . strtr($options['search'], ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            foreach ([':search_code', ':search_ar', ':search_en'] as $parameter) {
                $statement->bindValue($parameter, $pattern, PDO::PARAM_STR);
            }
            $statement->bindValue(':limit', $options['limit'], PDO::PARAM_INT);
            $statement->bindValue(':offset', $options['offset'], PDO::PARAM_INT);
            $statement->execute();
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        return array_map(fn (array $row): array => $this->mapAttributeOption($row), $rows);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return AttributeOptionRecord
     */
    public function createAttributeOption(int|string $definitionId, array $attributes): array
    {
        $definitionId = $this->identifier($definitionId);
        $this->writeFields($attributes, ['ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id'], ['ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance']);
        $attributes['attribute_definition_id'] = $definitionId;
        $id = $this->queryBuilder->table('attribute_options')->insert($attributes);
        $record = $this->findAttributeOptionById($id);
        if ($record === null) {
            throw new RuntimeException('Created AttributeOption could not be retrieved.');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $changes
     * @return AttributeOptionRecord|null
     */
    public function updateAttributeOption(int|string $definitionId, int|string $optionId, array $changes): ?array
    {
        $id = $this->identifier($optionId);
        $definitionId = $this->identifier($definitionId);
        $this->writeFields($changes, ['name_ar', 'name_en', 'status', 'sort_order', 'updated_by_user_id']);
        if ($changes !== []) {
            $this->queryBuilder->table('attribute_options')->where('id', '=', $id)
                ->where('attribute_definition_id', '=', $definitionId)
                ->update($changes);
        }

        $row = $this->queryBuilder->table('attribute_options')->select(self::ATTRIBUTE_OPTION_COLUMNS)
            ->where('id', '=', $id)->where('attribute_definition_id', '=', $definitionId)->first();

        return $row === null ? null : $this->mapAttributeOption($row);
    }

    public function deleteAttributeOption(int|string $definitionId, int|string $optionId): bool
    {
        $id = $this->identifier($optionId);
        $definitionId = $this->identifier($definitionId);

        return $this->queryBuilder->table('attribute_options')->where('id', '=', $id)
            ->where('attribute_definition_id', '=', $definitionId)
            ->delete() > 0;
    }

    /** Caller owns the transaction. @return AttributeOptionRecord|null */
    public function findAttributeOptionForUpdate(int|string $definitionId, int|string $optionId): ?array
    {
        $definitionId = $this->identifier($definitionId);
        $optionId = $this->identifier($optionId);
        $row = $this->queryBuilder->table('attribute_options')->select(self::ATTRIBUTE_OPTION_COLUMNS)
            ->where('attribute_definition_id', '=', $definitionId)->where('id', '=', $optionId)
            ->forUpdate()->first();

        return $row === null ? null : $this->mapAttributeOption($row);
    }

    /** Caller owns the transaction. @return list<AttributeOptionRecord> */
    public function listActiveAttributeOptionsForUpdate(int|string $definitionId): array
    {
        $definitionId = $this->identifier($definitionId);
        $rows = $this->queryBuilder->table('attribute_options')->select(self::ATTRIBUTE_OPTION_COLUMNS)
            ->where('attribute_definition_id', '=', $definitionId)->where('status', '=', 'active')
            ->orderBy('id')->forUpdate()->get();

        return array_map(fn (array $row): array => $this->mapAttributeOption($row), $rows);
    }

    /**
     * @param list<int|string> $definitionIds
     * @return list<AttributeOptionRecord>
     */
    public function listAttributeOptionsForDefinitions(array $definitionIds): array
    {
        $ids = array_map(fn (mixed $id): int => $this->identifier($id), array_values($definitionIds));
        if ($ids === []) {
            return [];
        }
        $rows = $this->queryBuilder->table('attribute_options')->select(self::ATTRIBUTE_OPTION_COLUMNS)
            ->whereIn('attribute_definition_id', array_values(array_unique($ids)))
            ->orderBy('attribute_definition_id')->orderBy('sort_order')
            ->orderBy('id')->get();

        return array_map(fn (array $row): array => $this->mapAttributeOption($row), $rows);
    }

    /**
     * @param array<string, mixed> $row
     * @return AttributeOptionRecord
     */
    private function mapAttributeOption(array $row): array
    {
        return [
            'id' => $this->storedInteger($row['id']),
            'ulid' => (string) $row['ulid'],
            'attribute_definition_id' => $this->storedInteger($row['attribute_definition_id']),
            'code' => (string) $row['code'],
            'name_ar' => (string) $row['name_ar'],
            'name_en' => (string) $row['name_en'],
            'status' => (string) $row['status'],
            'provenance' => (string) $row['provenance'],
            'sort_order' => $this->storedInteger($row['sort_order']),
            'created_by_user_id' => $row['created_by_user_id'] === null ? null : $this->storedInteger($row['created_by_user_id']),
            'updated_by_user_id' => $row['updated_by_user_id'] === null ? null : $this->storedInteger($row['updated_by_user_id']),
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    /**
     * Validate the small list contract before touching the stateful QueryBuilder.
     * @param array<string, mixed> $options
     * @param list<string> $extraKeys
     * @return array<string, mixed>
     */
    private function listOptions(array $options, array $extraKeys = []): array
    {
        $allowed = array_merge(['status', 'search', 'limit', 'offset'], $extraKeys);
        foreach (array_keys($options) as $key) {
            if (! in_array($key, $allowed, true)) {
                throw new InvalidArgumentException('Unknown list option.');
            }
        }
        $options += ['status' => null, 'search' => '', 'limit' => 50, 'offset' => 0];
        if ($options['status'] !== null && ! is_string($options['status'])) {
            throw new InvalidArgumentException('status must be a string or null.');
        }
        if (! is_string($options['search'])) {
            throw new InvalidArgumentException('search must be a string.');
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
}
