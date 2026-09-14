<?php

declare(strict_types=1);

namespace App\Modules\Property\Repositories;

use App\Core\Database\DatabaseConnectionInterface;
use App\Core\Database\QueryBuilderInterface;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * Canonical catalog persistence only; no authorization, lifecycle, or transaction ownership.
 * Read models expose explicit resource fields without hidden joins.
 * location_type maps explicitly to the existing geographic_locations.type column.
 *
 * @phpstan-type GeographicLocationRecord array{
 *     id: int,
 *     ulid: string,
 *     code: string,
 *     name_ar: string,
 *     name_en: string,
 *     location_type: string,
 *     parent_id: int|null,
 *     status: string,
 *     provenance: string,
 *     sort_order: int,
 *     created_by_user_id: int|null,
 *     updated_by_user_id: int|null,
 *     created_at: string,
 *     updated_at: string,
 * }
 * @phpstan-type GeographicAncestryRead array{
 *     locations: list<GeographicLocationRecord>,
 *     stop_reason: 'root'|'missing_parent'|'cycle'|'depth_limit'
 * }
 */
final class GeographicLocationRepository
{
    private const LOCATION_COLUMNS = [
        'id', 'ulid', 'code', 'name_ar', 'name_en', 'type', 'parent_id', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id', 'created_at', 'updated_at',
    ];

    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private DatabaseConnectionInterface $database
    ) {
    }

    /** @return GeographicLocationRecord|null */
    public function findGeographicLocationById(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('geographic_locations')->select(self::LOCATION_COLUMNS)
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : $this->mapGeographicLocation($row);
    }

    /** @return GeographicLocationRecord|null */
    public function findGeographicLocationByUlid(string $ulid): ?array
    {
        $row = $this->queryBuilder->table('geographic_locations')->select(self::LOCATION_COLUMNS)
            ->where('ulid', '=', $ulid)
            ->first();

        return $row === null ? null : $this->mapGeographicLocation($row);
    }

    /** @return GeographicLocationRecord|null */
    public function findGeographicLocationByCode(string $code): ?array
    {
        $row = $this->queryBuilder->table('geographic_locations')->select(self::LOCATION_COLUMNS)
            ->where('code', '=', $code)
            ->first();

        return $row === null ? null : $this->mapGeographicLocation($row);
    }

    /** Advisory only; uniqueness remains enforced by the database. */
    public function geographicLocationExistsByCode(string $code): bool
    {
        return $this->findGeographicLocationByCode($code) !== null;
    }

    /**
     * Nullable reference filters: omitted = unrestricted; null = IS NULL; numeric = equality.
     * @param array{status?: ?string, search?: string, limit?: int, offset?: int, location_type?: string, parent_id?: int|string|null} $options
     * @return list<GeographicLocationRecord>
     */
    public function listGeographicLocations(array $options = []): array
    {
        $options = $this->listOptions($options, ['location_type', 'parent_id']);
        if (array_key_exists('location_type', $options) && ! is_string($options['location_type'])) {
            throw new InvalidArgumentException('location_type must be a string.');
        }
        if (array_key_exists('parent_id', $options) && $options['parent_id'] !== null) {
            $options['parent_id'] = $this->identifier($options['parent_id']);
        }
        $hasNullFilter = (array_key_exists('parent_id', $options) && $options['parent_id'] === null);

        if ($options['search'] === '' && ! $hasNullFilter) {
            $query = $this->queryBuilder->table('geographic_locations')->select(self::LOCATION_COLUMNS);
            if ($options['status'] !== null) {
                $query->where('status', '=', $options['status']);
            }
            if (array_key_exists('location_type', $options)) {
                $query->where('type', '=', $options['location_type']);
            }
            if (array_key_exists('parent_id', $options)) {
                $query->where('parent_id', '=', $options['parent_id']);
            }
            $rows = $query->orderBy('sort_order')->orderBy('id')
                ->limit($options['limit'])->offset($options['offset'])->get();
        } else {
            // QueryBuilder cannot express grouped search or IS NULL predicates.
            $sql = 'SELECT `id`, `ulid`, `code`, `name_ar`, `name_en`, `type`, `parent_id`, `status`, `provenance`, `sort_order`, `created_by_user_id`, `updated_by_user_id`, `created_at`, `updated_at`'
                . ' FROM `geographic_locations` WHERE 1 = 1';
            if ($options['status'] !== null) {
                $sql .= ' AND `status` = :status';
            }
            if (array_key_exists('location_type', $options)) {
                $sql .= ' AND `type` = :location_type';
            }
            if (array_key_exists('parent_id', $options)) {
                $sql .= $options['parent_id'] === null
                    ? ' AND `parent_id` IS NULL'
                    : ' AND `parent_id` = :parent_id';
            }
            if ($options['search'] !== '') {
                $sql .= " AND (`code` LIKE :search_code ESCAPE '!'"
                    . " OR `name_ar` LIKE :search_ar ESCAPE '!'"
                    . " OR `name_en` LIKE :search_en ESCAPE '!')";
            }
            $sql .= ' ORDER BY `sort_order` ASC, `id` ASC LIMIT :limit OFFSET :offset';
            $statement = $this->database->connection()->prepare($sql);
            if ($statement === false) {
                throw new RuntimeException('Unable to prepare catalog list.');
            }
            if ($options['status'] !== null) {
                $statement->bindValue(':status', $options['status'], PDO::PARAM_STR);
            }
            if (array_key_exists('location_type', $options)) {
                $statement->bindValue(':location_type', $options['location_type'], PDO::PARAM_STR);
            }
            if (array_key_exists('parent_id', $options) && $options['parent_id'] !== null) {
                $statement->bindValue(':parent_id', $options['parent_id'], PDO::PARAM_INT);
            }
            if ($options['search'] !== '') {
                $pattern = '%' . strtr($options['search'], ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
                foreach ([':search_code', ':search_ar', ':search_en'] as $parameter) {
                    $statement->bindValue($parameter, $pattern, PDO::PARAM_STR);
                }
            }
            $statement->bindValue(':limit', $options['limit'], PDO::PARAM_INT);
            $statement->bindValue(':offset', $options['offset'], PDO::PARAM_INT);
            $statement->execute();
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        return array_map(fn (array $row): array => $this->mapGeographicLocation($row), $rows);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return GeographicLocationRecord
     */
    public function createGeographicLocation(array $attributes): array
    {
        $this->writeFields($attributes, ['ulid', 'code', 'name_ar', 'name_en', 'location_type', 'parent_id', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id'], ['ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'location_type']);
        // The public contract calls the physical type column location_type.
        $attributes['type'] = $attributes['location_type'];
        unset($attributes['location_type']);
        $id = $this->queryBuilder->table('geographic_locations')->insert($attributes);
        $record = $this->findGeographicLocationById($id);
        if ($record === null) {
            throw new RuntimeException('Created GeographicLocation could not be retrieved.');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $changes
     * @return GeographicLocationRecord|null
     */
    public function updateGeographicLocation(int|string $id, array $changes): ?array
    {
        $id = $this->identifier($id);
        $this->writeFields($changes, ['name_ar', 'name_en', 'location_type', 'parent_id', 'status', 'sort_order', 'updated_by_user_id']);
        if (array_key_exists('location_type', $changes)) {
            $changes['type'] = $changes['location_type'];
            unset($changes['location_type']);
        }
        if ($changes !== []) {
            $this->queryBuilder->table('geographic_locations')->where('id', '=', $id)
                ->update($changes);
        }

        return $this->findGeographicLocationById($id);
    }

    public function deleteGeographicLocation(int|string $id): bool
    {
        $id = $this->identifier($id);

        return $this->queryBuilder->table('geographic_locations')->where('id', '=', $id)
            ->delete() > 0;
    }

    /**
     * @param array<string, mixed> $row
     * @return GeographicLocationRecord
     */
    private function mapGeographicLocation(array $row): array
    {
        return [
            'id' => $this->storedInteger($row['id']),
            'ulid' => (string) $row['ulid'],
            'code' => (string) $row['code'],
            'name_ar' => (string) $row['name_ar'],
            'name_en' => (string) $row['name_en'],
            'location_type' => (string) $row['type'],
            'parent_id' => $row['parent_id'] === null ? null : $this->storedInteger($row['parent_id']),
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
     * Search is supported through the same safe list implementation.
     * @param array{status?: ?string, search?: string, location_type?: string, limit?: int, offset?: int} $options
     * @return list<GeographicLocationRecord>
     */
    public function listChildren(int|string $parentId, array $options = []): array
    {
        $parentId = $this->identifier($parentId);
        $options = $this->listOptions($options, ['location_type']);
        $options['parent_id'] = $parentId;

        return $this->listGeographicLocations($options);
    }

    /**
     * Read-only traversal of persisted references, not hierarchy validity or mutation policy.
     * A known root or repeated ID takes precedence over the traversal depth bound.
     * Caller supplies an appropriate transaction if a consistent ancestry snapshot is needed.
     * @return GeographicAncestryRead
     */
    public function loadAncestry(int|string $locationId, int $maxDepth = 32): array
    {
        $locationId = $this->identifier($locationId);
        if ($maxDepth < 1 || $maxDepth > 256) {
            throw new InvalidArgumentException('maxDepth must be between 1 and 256.');
        }

        $locations = [];
        $visited = [];
        while (true) {
            if (isset($visited[$locationId])) {
                return ['locations' => $locations, 'stop_reason' => 'cycle'];
            }
            if (count($locations) >= $maxDepth) {
                return ['locations' => $locations, 'stop_reason' => 'depth_limit'];
            }
            $location = $this->findGeographicLocationById($locationId);
            if ($location === null) {
                return ['locations' => $locations, 'stop_reason' => 'missing_parent'];
            }

            $locations[] = $location;
            $visited[$location['id']] = true;
            if ($location['parent_id'] === null) {
                return ['locations' => $locations, 'stop_reason' => 'root'];
            }
            $locationId = $location['parent_id'];
        }
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
