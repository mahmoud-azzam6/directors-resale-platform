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
 * @phpstan-type PropertyCategoryRecord array{
 *     id: int,
 *     ulid: string,
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
 * @phpstan-type UnitTypeRecord array{
 *     id: int,
 *     ulid: string,
 *     property_category_id: int,
 *     code: string,
 *     name_ar: string,
 *     name_en: string,
 *     status: string,
 *     provenance: string,
 *     sort_order: int,
 *     last_allocated_configuration_version: int,
 *     created_by_user_id: int|null,
 *     updated_by_user_id: int|null,
 *     created_at: string,
 *     updated_at: string,
 * }
 */
final class PropertyCatalogRepository
{
    private const CATEGORY_COLUMNS = ['id', 'ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id', 'created_at', 'updated_at'];

    private const UNIT_TYPE_COLUMNS = ['id', 'ulid', 'property_category_id', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'sort_order', 'last_allocated_configuration_version', 'created_by_user_id', 'updated_by_user_id', 'created_at', 'updated_at'];

    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private DatabaseConnectionInterface $database
    ) {
    }

    /** @return PropertyCategoryRecord|null */
    public function findCategoryById(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('property_categories')->select(self::CATEGORY_COLUMNS)
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : $this->mapCategory($row);
    }

    /** @return PropertyCategoryRecord|null */
    public function findCategoryByUlid(string $ulid): ?array
    {
        $row = $this->queryBuilder->table('property_categories')->select(self::CATEGORY_COLUMNS)
            ->where('ulid', '=', $ulid)
            ->first();

        return $row === null ? null : $this->mapCategory($row);
    }

    /** @return PropertyCategoryRecord|null */
    public function findCategoryByCode(string $code): ?array
    {
        $row = $this->queryBuilder->table('property_categories')->select(self::CATEGORY_COLUMNS)
            ->where('code', '=', $code)
            ->first();

        return $row === null ? null : $this->mapCategory($row);
    }

    /** Advisory only; uniqueness remains enforced by the database. */
    public function categoryExistsByCode(string $code): bool
    {
        return $this->findCategoryByCode($code) !== null;
    }

    /**
     * @param array{status?: ?string, search?: string, limit?: int, offset?: int} $options
     * @return list<PropertyCategoryRecord>
     */
    public function listCategories(array $options = []): array
    {
        $options = $this->listOptions($options, []);

        if ($options['search'] === '') {
            $query = $this->queryBuilder->table('property_categories')->select(self::CATEGORY_COLUMNS);
            if ($options['status'] !== null) {
                $query->where('status', '=', $options['status']);
            }
            $rows = $query->orderBy('sort_order')->orderBy('id')
                ->limit($options['limit'])->offset($options['offset'])->get();
        } else {
            // QueryBuilder cannot group OR predicates under the status/parent filters.
            $sql = 'SELECT `id`, `ulid`, `code`, `name_ar`, `name_en`, `status`, `provenance`, `sort_order`, `created_by_user_id`, `updated_by_user_id`, `created_at`, `updated_at`'
                . ' FROM `property_categories` WHERE 1 = 1';
            if ($options['status'] !== null) {
                $sql .= ' AND `status` = :status';
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
            $pattern = '%' . strtr($options['search'], ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            foreach ([':search_code', ':search_ar', ':search_en'] as $parameter) {
                $statement->bindValue($parameter, $pattern, PDO::PARAM_STR);
            }
            $statement->bindValue(':limit', $options['limit'], PDO::PARAM_INT);
            $statement->bindValue(':offset', $options['offset'], PDO::PARAM_INT);
            $statement->execute();
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        return array_map(fn (array $row): array => $this->mapCategory($row), $rows);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return PropertyCategoryRecord
     */
    public function createCategory(array $attributes): array
    {
        $this->writeFields($attributes, ['ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id'], ['ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance']);
        $id = $this->queryBuilder->table('property_categories')->insert($attributes);
        $record = $this->findCategoryById($id);
        if ($record === null) {
            throw new RuntimeException('Created Category could not be retrieved.');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $changes
     * @return PropertyCategoryRecord|null
     */
    public function updateCategory(int|string $id, array $changes): ?array
    {
        $id = $this->identifier($id);
        $this->writeFields($changes, ['name_ar', 'name_en', 'status', 'sort_order', 'updated_by_user_id']);
        if ($changes !== []) {
            $this->queryBuilder->table('property_categories')->where('id', '=', $id)
                ->update($changes);
        }

        return $this->findCategoryById($id);
    }

    public function deleteCategory(int|string $id): bool
    {
        $id = $this->identifier($id);

        return $this->queryBuilder->table('property_categories')->where('id', '=', $id)
            ->delete() > 0;
    }

    /** Caller owns the transaction. @return PropertyCategoryRecord|null */
    public function findCategoryForUpdate(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('property_categories')->select(self::CATEGORY_COLUMNS)
            ->where('id', '=', $id)->forUpdate()->first();

        return $row === null ? null : $this->mapCategory($row);
    }

    public function hasUnitTypesForCategory(int|string $categoryId, ?string $status = null): bool
    {
        $categoryId = $this->identifier($categoryId);
        $query = $this->queryBuilder->table('unit_types')->select(['id'])
            ->where('property_category_id', '=', $categoryId);
        if ($status !== null) {
            $query->where('status', '=', $status);
        }

        return $query->first() !== null;
    }

    /**
     * @param array<string, mixed> $row
     * @return PropertyCategoryRecord
     */
    private function mapCategory(array $row): array
    {
        return [
            'id' => $this->storedInteger($row['id']),
            'ulid' => (string) $row['ulid'],
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

    /** @return UnitTypeRecord|null */
    public function findUnitTypeById(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('unit_types')->select(self::UNIT_TYPE_COLUMNS)
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : $this->mapUnitType($row);
    }

    /** @return UnitTypeRecord|null */
    public function findUnitTypeByUlid(string $ulid): ?array
    {
        $row = $this->queryBuilder->table('unit_types')->select(self::UNIT_TYPE_COLUMNS)
            ->where('ulid', '=', $ulid)
            ->first();

        return $row === null ? null : $this->mapUnitType($row);
    }

    /** @return UnitTypeRecord|null */
    public function findUnitTypeByCode(string $code): ?array
    {
        $row = $this->queryBuilder->table('unit_types')->select(self::UNIT_TYPE_COLUMNS)
            ->where('code', '=', $code)
            ->first();

        return $row === null ? null : $this->mapUnitType($row);
    }

    /** Advisory only; uniqueness remains enforced by the database. */
    public function unitTypeExistsByCode(string $code): bool
    {
        return $this->findUnitTypeByCode($code) !== null;
    }

    /**
     * @param array{status?: ?string, search?: string, limit?: int, offset?: int, property_category_id?: int|string} $options
     * @return list<UnitTypeRecord>
     */
    public function listUnitTypes(array $options = []): array
    {
        $options = $this->listOptions($options, ['property_category_id']);
        if (array_key_exists('property_category_id', $options)) {
            $options['property_category_id'] = $this->identifier($options['property_category_id']);
        }

        if ($options['search'] === '') {
            $query = $this->queryBuilder->table('unit_types')->select(self::UNIT_TYPE_COLUMNS);
            if ($options['status'] !== null) {
                $query->where('status', '=', $options['status']);
            }
            if (array_key_exists('property_category_id', $options)) {
                $query->where('property_category_id', '=', $options['property_category_id']);
            }
            $rows = $query->orderBy('sort_order')->orderBy('id')
                ->limit($options['limit'])->offset($options['offset'])->get();
        } else {
            // QueryBuilder cannot group OR predicates under the status/parent filters.
            $sql = 'SELECT `id`, `ulid`, `property_category_id`, `code`, `name_ar`, `name_en`, `status`, `provenance`, `sort_order`, `last_allocated_configuration_version`, `created_by_user_id`, `updated_by_user_id`, `created_at`, `updated_at`'
                . ' FROM `unit_types` WHERE 1 = 1';
            if ($options['status'] !== null) {
                $sql .= ' AND `status` = :status';
            }
            if (array_key_exists('property_category_id', $options)) {
                $sql .= ' AND `property_category_id` = :property_category_id';
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
            if (array_key_exists('property_category_id', $options)) {
                $statement->bindValue(':property_category_id', $options['property_category_id'], PDO::PARAM_INT);
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

        return array_map(fn (array $row): array => $this->mapUnitType($row), $rows);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return UnitTypeRecord
     */
    public function createUnitType(array $attributes): array
    {
        $this->writeFields($attributes, ['ulid', 'property_category_id', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id'], ['ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'property_category_id']);
        $id = $this->queryBuilder->table('unit_types')->insert($attributes);
        $record = $this->findUnitTypeById($id);
        if ($record === null) {
            throw new RuntimeException('Created UnitType could not be retrieved.');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $changes
     * @return UnitTypeRecord|null
     */
    public function updateUnitType(int|string $id, array $changes): ?array
    {
        $id = $this->identifier($id);
        $this->writeFields($changes, ['name_ar', 'name_en', 'status', 'sort_order', 'updated_by_user_id', 'property_category_id']);
        if ($changes !== []) {
            $this->queryBuilder->table('unit_types')->where('id', '=', $id)
                ->update($changes);
        }

        return $this->findUnitTypeById($id);
    }

    public function deleteUnitType(int|string $id): bool
    {
        $id = $this->identifier($id);

        return $this->queryBuilder->table('unit_types')->where('id', '=', $id)
            ->delete() > 0;
    }

    /**
     * Persistence only. Caller must hold the Unit Type FOR UPDATE lock and
     * enforce the monotonic allocation policy in its transaction.
     * @return UnitTypeRecord|null
     */
    public function updateLastAllocatedConfigurationVersion(
        int|string $id,
        int $lastAllocatedConfigurationVersion
    ): ?array {
        $id = $this->identifier($id);
        if ($lastAllocatedConfigurationVersion < 0 || $lastAllocatedConfigurationVersion > 4294967295) {
            throw new InvalidArgumentException('last_allocated_configuration_version is outside the supported range.');
        }
        $this->queryBuilder->table('unit_types')->where('id', '=', $id)
            ->update(['last_allocated_configuration_version' => $lastAllocatedConfigurationVersion]);

        return $this->findUnitTypeById($id);
    }

    /**
     * @param array<string, mixed> $row
     * @return UnitTypeRecord
     */
    private function mapUnitType(array $row): array
    {
        return [
            'id' => $this->storedInteger($row['id']),
            'ulid' => (string) $row['ulid'],
            'property_category_id' => $this->storedInteger($row['property_category_id']),
            'code' => (string) $row['code'],
            'name_ar' => (string) $row['name_ar'],
            'name_en' => (string) $row['name_en'],
            'status' => (string) $row['status'],
            'provenance' => (string) $row['provenance'],
            'sort_order' => $this->storedInteger($row['sort_order']),
            'last_allocated_configuration_version' => $this->storedInteger($row['last_allocated_configuration_version']),
            'created_by_user_id' => $row['created_by_user_id'] === null ? null : $this->storedInteger($row['created_by_user_id']),
            'updated_by_user_id' => $row['updated_by_user_id'] === null ? null : $this->storedInteger($row['updated_by_user_id']),
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @return list<UnitTypeRecord>
     */
    public function listUnitTypesByCategory(int|string $categoryId, array $options = []): array
    {
        $categoryId = $this->identifier($categoryId);
        if (array_key_exists('property_category_id', $options)
            && $this->identifier($options['property_category_id']) !== $categoryId) {
            throw new InvalidArgumentException('Conflicting property_category_id filter.');
        }
        $options['property_category_id'] = $categoryId;

        return $this->listUnitTypes($options);
    }

    /**
     * Caller owns the transaction.
     * @return UnitTypeRecord|null
     */
    public function findUnitTypeForUpdate(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('unit_types')->select(self::UNIT_TYPE_COLUMNS)
            ->where('id', '=', $id)->forUpdate()->first();

        return $row === null ? null : $this->mapUnitType($row);
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
