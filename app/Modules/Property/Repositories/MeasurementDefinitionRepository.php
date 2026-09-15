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
 * @phpstan-type MeasurementDefinitionRecord array{
 *     id: int,
 *     ulid: string,
 *     code: string,
 *     name_ar: string,
 *     name_en: string,
 *     default_unit_code: string,
 *     status: string,
 *     provenance: string,
 *     sort_order: int,
 *     created_by_user_id: int|null,
 *     updated_by_user_id: int|null,
 *     created_at: string,
 *     updated_at: string,
 * }
 */
final class MeasurementDefinitionRepository
{
    private const MEASUREMENT_DEFINITION_COLUMNS = ['id', 'ulid', 'code', 'name_ar', 'name_en', 'default_unit_code', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id', 'created_at', 'updated_at'];

    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private DatabaseConnectionInterface $database
    ) {
    }

    /** @return MeasurementDefinitionRecord|null */
    public function findMeasurementDefinitionById(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('measurement_definitions')->select(self::MEASUREMENT_DEFINITION_COLUMNS)
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : $this->mapMeasurementDefinition($row);
    }

    /** @return MeasurementDefinitionRecord|null */
    public function findMeasurementDefinitionByUlid(string $ulid): ?array
    {
        $row = $this->queryBuilder->table('measurement_definitions')->select(self::MEASUREMENT_DEFINITION_COLUMNS)
            ->where('ulid', '=', $ulid)
            ->first();

        return $row === null ? null : $this->mapMeasurementDefinition($row);
    }

    /** @return MeasurementDefinitionRecord|null */
    public function findMeasurementDefinitionByCode(string $code): ?array
    {
        $row = $this->queryBuilder->table('measurement_definitions')->select(self::MEASUREMENT_DEFINITION_COLUMNS)
            ->where('code', '=', $code)
            ->first();

        return $row === null ? null : $this->mapMeasurementDefinition($row);
    }

    /** Advisory only; uniqueness remains enforced by the database. */
    public function measurementDefinitionExistsByCode(string $code): bool
    {
        return $this->findMeasurementDefinitionByCode($code) !== null;
    }

    /**
     * @param array{status?: ?string, search?: string, limit?: int, offset?: int} $options
     * @return list<MeasurementDefinitionRecord>
     */
    public function listMeasurementDefinitions(array $options = []): array
    {
        $options = $this->listOptions($options, []);

        if ($options['search'] === '') {
            $query = $this->queryBuilder->table('measurement_definitions')->select(self::MEASUREMENT_DEFINITION_COLUMNS);
            if ($options['status'] !== null) {
                $query->where('status', '=', $options['status']);
            }
            $rows = $query->orderBy('sort_order')->orderBy('id')
                ->limit($options['limit'])->offset($options['offset'])->get();
        } else {
            // QueryBuilder cannot group OR predicates under the status/parent filters.
            $sql = 'SELECT `id`, `ulid`, `code`, `name_ar`, `name_en`, `default_unit_code`, `status`, `provenance`, `sort_order`, `created_by_user_id`, `updated_by_user_id`, `created_at`, `updated_at`'
                . ' FROM `measurement_definitions` WHERE 1 = 1';
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

        return array_map(fn (array $row): array => $this->mapMeasurementDefinition($row), $rows);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return MeasurementDefinitionRecord
     */
    public function createMeasurementDefinition(array $attributes): array
    {
        $this->writeFields($attributes, ['ulid', 'code', 'name_ar', 'name_en', 'default_unit_code', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id'], ['ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'default_unit_code']);
        $id = $this->queryBuilder->table('measurement_definitions')->insert($attributes);
        $record = $this->findMeasurementDefinitionById($id);
        if ($record === null) {
            throw new RuntimeException('Created MeasurementDefinition could not be retrieved.');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $changes
     * @return MeasurementDefinitionRecord|null
     */
    public function updateMeasurementDefinition(int|string $id, array $changes): ?array
    {
        $id = $this->identifier($id);
        $this->writeFields($changes, ['name_ar', 'name_en', 'status', 'sort_order', 'updated_by_user_id', 'default_unit_code']);
        if ($changes !== []) {
            $this->queryBuilder->table('measurement_definitions')->where('id', '=', $id)
                ->update($changes);
        }

        return $this->findMeasurementDefinitionById($id);
    }

    public function deleteMeasurementDefinition(int|string $id): bool
    {
        $id = $this->identifier($id);

        return $this->queryBuilder->table('measurement_definitions')->where('id', '=', $id)
            ->delete() > 0;
    }

    /** Caller owns the transaction. @return MeasurementDefinitionRecord|null */
    public function findMeasurementDefinitionForUpdate(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('measurement_definitions')->select(self::MEASUREMENT_DEFINITION_COLUMNS)
            ->where('id', '=', $id)->forUpdate()->first();

        return $row === null ? null : $this->mapMeasurementDefinition($row);
    }

    /**
     * @param list<int|string> $ids
     * @return list<MeasurementDefinitionRecord>
     */
    public function findMeasurementDefinitionsByIds(array $ids): array
    {
        $ids = array_map(fn (mixed $id): int => $this->identifier($id), array_values($ids));
        if ($ids === []) {
            return [];
        }
        $rows = $this->queryBuilder->table('measurement_definitions')->select(self::MEASUREMENT_DEFINITION_COLUMNS)
            ->whereIn('id', array_values(array_unique($ids)))
            ->orderBy('id')->get();

        return array_map(fn (array $row): array => $this->mapMeasurementDefinition($row), $rows);
    }

    /**
     * @param array<string, mixed> $row
     * @return MeasurementDefinitionRecord
     */
    private function mapMeasurementDefinition(array $row): array
    {
        return [
            'id' => $this->storedInteger($row['id']),
            'ulid' => (string) $row['ulid'],
            'code' => (string) $row['code'],
            'name_ar' => (string) $row['name_ar'],
            'name_en' => (string) $row['name_en'],
            'default_unit_code' => (string) $row['default_unit_code'],
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
