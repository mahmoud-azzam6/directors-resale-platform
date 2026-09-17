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
 *
 * @phpstan-type DeveloperRecord array{
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
 * @phpstan-type ProjectRecord array{
 *     id: int,
 *     ulid: string,
 *     code: string,
 *     name_ar: string,
 *     name_en: string,
 *     developer_id: int|null,
 *     geographic_location_id: int|null,
 *     status: string,
 *     provenance: string,
 *     sort_order: int,
 *     created_by_user_id: int|null,
 *     updated_by_user_id: int|null,
 *     created_at: string,
 *     updated_at: string,
 * }
 * @phpstan-type ProjectPhaseRecord array{
 *     id: int,
 *     ulid: string,
 *     project_id: int,
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
final class DevelopmentCatalogRepository
{
    private const DEVELOPER_COLUMNS = [
        'id', 'ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id', 'created_at', 'updated_at',
    ];

    private const PROJECT_COLUMNS = [
        'id', 'ulid', 'code', 'name_ar', 'name_en', 'developer_id', 'geographic_location_id', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id', 'created_at', 'updated_at',
    ];

    private const PHASE_COLUMNS = [
        'id', 'ulid', 'project_id', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id', 'created_at', 'updated_at',
    ];

    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private DatabaseConnectionInterface $database
    ) {
    }

    /** Caller owns the transaction and parent-before-child lock protocol. */
    public function findDeveloperForUpdate(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('developers')->select(self::DEVELOPER_COLUMNS)
            ->where('id', '=', $id)->forUpdate()->first();

        return $row === null ? null : $this->mapDeveloper($row);
    }

    /** Caller owns the transaction and parent-before-child lock protocol. */
    public function findProjectForUpdate(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('projects')->select(self::PROJECT_COLUMNS)
            ->where('id', '=', $id)->forUpdate()->first();

        return $row === null ? null : $this->mapProject($row);
    }

    /** Project scope is part of the persistence predicate. */
    public function findProjectPhaseForUpdate(int|string $projectId, int|string $phaseId): ?array
    {
        $projectId = $this->identifier($projectId);
        $phaseId = $this->identifier($phaseId);
        $row = $this->queryBuilder->table('project_phases')->select(self::PHASE_COLUMNS)
            ->where('id', '=', $phaseId)->where('project_id', '=', $projectId)->forUpdate()->first();

        return $row === null ? null : $this->mapProjectPhase($row);
    }

    /** Current reference read; caller must first lock the Developer. */
    public function hasProjectsForDeveloper(int|string $developerId, ?string $status = null): bool
    {
        $developerId = $this->identifier($developerId);
        $query = $this->queryBuilder->table('projects')->select(['id'])->where('developer_id', '=', $developerId);
        if ($status !== null) { $query->where('status', '=', $status); }

        return $query->orderBy('id')->forUpdate()->first() !== null;
    }

    /** Current reference read; caller must first lock the Project. */
    public function hasPhasesForProject(int|string $projectId, ?string $status = null): bool
    {
        $projectId = $this->identifier($projectId);
        $query = $this->queryBuilder->table('project_phases')->select(['id'])->where('project_id', '=', $projectId);
        if ($status !== null) { $query->where('status', '=', $status); }

        return $query->orderBy('id')->forUpdate()->first() !== null;
    }

    /** @return DeveloperRecord|null */
    public function findDeveloperById(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('developers')->select(self::DEVELOPER_COLUMNS)
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : $this->mapDeveloper($row);
    }

    /** @return DeveloperRecord|null */
    public function findDeveloperByUlid(string $ulid): ?array
    {
        $row = $this->queryBuilder->table('developers')->select(self::DEVELOPER_COLUMNS)
            ->where('ulid', '=', $ulid)
            ->first();

        return $row === null ? null : $this->mapDeveloper($row);
    }

    /** @return DeveloperRecord|null */
    public function findDeveloperByCode(string $code): ?array
    {
        $row = $this->queryBuilder->table('developers')->select(self::DEVELOPER_COLUMNS)
            ->where('code', '=', $code)
            ->first();

        return $row === null ? null : $this->mapDeveloper($row);
    }

    /** Advisory only; uniqueness remains enforced by the database. */
    public function developerExistsByCode(string $code): bool
    {
        return $this->findDeveloperByCode($code) !== null;
    }

    /**
     * Nullable reference filters: omitted = unrestricted; null = IS NULL; numeric = equality.
     * @param array{status?: ?string, search?: string, limit?: int, offset?: int} $options
     * @return list<DeveloperRecord>
     */
    public function listDevelopers(array $options = []): array
    {
        $options = $this->listOptions($options, []);

        if ($options['search'] === '') {
            $query = $this->queryBuilder->table('developers')->select(self::DEVELOPER_COLUMNS);
            if ($options['status'] !== null) {
                $query->where('status', '=', $options['status']);
            }
            $rows = $query->orderBy('sort_order')->orderBy('id')
                ->limit($options['limit'])->offset($options['offset'])->get();
        } else {
            // QueryBuilder cannot express grouped search or IS NULL predicates.
            $sql = 'SELECT `id`, `ulid`, `code`, `name_ar`, `name_en`, `status`, `provenance`, `sort_order`, `created_by_user_id`, `updated_by_user_id`, `created_at`, `updated_at`'
                . ' FROM `developers` WHERE 1 = 1';
            if ($options['status'] !== null) {
                $sql .= ' AND `status` = :status';
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

        return array_map(fn (array $row): array => $this->mapDeveloper($row), $rows);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return DeveloperRecord
     */
    public function createDeveloper(array $attributes): array
    {
        $this->writeFields($attributes, ['ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id'], ['ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance']);
        $id = $this->queryBuilder->table('developers')->insert($attributes);
        $record = $this->findDeveloperById($id);
        if ($record === null) {
            throw new RuntimeException('Created Developer could not be retrieved.');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $changes
     * @return DeveloperRecord|null
     */
    public function updateDeveloper(int|string $id, array $changes): ?array
    {
        $id = $this->identifier($id);
        $this->writeFields($changes, ['name_ar', 'name_en', 'status', 'sort_order', 'updated_by_user_id']);
        if ($changes !== []) {
            $this->queryBuilder->table('developers')->where('id', '=', $id)
                ->update($changes);
        }

        return $this->findDeveloperById($id);
    }

    public function deleteDeveloper(int|string $id): bool
    {
        $id = $this->identifier($id);

        return $this->queryBuilder->table('developers')->where('id', '=', $id)
            ->delete() > 0;
    }

    /**
     * @param array<string, mixed> $row
     * @return DeveloperRecord
     */
    private function mapDeveloper(array $row): array
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

    /** @return ProjectRecord|null */
    public function findProjectById(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('projects')->select(self::PROJECT_COLUMNS)
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : $this->mapProject($row);
    }

    /** @return ProjectRecord|null */
    public function findProjectByUlid(string $ulid): ?array
    {
        $row = $this->queryBuilder->table('projects')->select(self::PROJECT_COLUMNS)
            ->where('ulid', '=', $ulid)
            ->first();

        return $row === null ? null : $this->mapProject($row);
    }

    /** @return ProjectRecord|null */
    public function findProjectByCode(string $code): ?array
    {
        $row = $this->queryBuilder->table('projects')->select(self::PROJECT_COLUMNS)
            ->where('code', '=', $code)
            ->first();

        return $row === null ? null : $this->mapProject($row);
    }

    /** Advisory only; uniqueness remains enforced by the database. */
    public function projectExistsByCode(string $code): bool
    {
        return $this->findProjectByCode($code) !== null;
    }

    /**
     * Nullable reference filters: omitted = unrestricted; null = IS NULL; numeric = equality.
     * @param array{status?: ?string, search?: string, limit?: int, offset?: int, developer_id?: int|string|null, geographic_location_id?: int|string|null} $options
     * @return list<ProjectRecord>
     */
    public function listProjects(array $options = []): array
    {
        $options = $this->listOptions($options, ['developer_id', 'geographic_location_id']);
        if (array_key_exists('developer_id', $options) && $options['developer_id'] !== null) {
            $options['developer_id'] = $this->identifier($options['developer_id']);
        }
        if (array_key_exists('geographic_location_id', $options) && $options['geographic_location_id'] !== null) {
            $options['geographic_location_id'] = $this->identifier($options['geographic_location_id']);
        }
        $hasNullFilter = (array_key_exists('developer_id', $options) && $options['developer_id'] === null)
            || (array_key_exists('geographic_location_id', $options) && $options['geographic_location_id'] === null);

        if ($options['search'] === '' && ! $hasNullFilter) {
            $query = $this->queryBuilder->table('projects')->select(self::PROJECT_COLUMNS);
            if ($options['status'] !== null) {
                $query->where('status', '=', $options['status']);
            }
            if (array_key_exists('developer_id', $options)) {
                $query->where('developer_id', '=', $options['developer_id']);
            }
            if (array_key_exists('geographic_location_id', $options)) {
                $query->where('geographic_location_id', '=', $options['geographic_location_id']);
            }
            $rows = $query->orderBy('sort_order')->orderBy('id')
                ->limit($options['limit'])->offset($options['offset'])->get();
        } else {
            // QueryBuilder cannot express grouped search or IS NULL predicates.
            $sql = 'SELECT `id`, `ulid`, `code`, `name_ar`, `name_en`, `developer_id`, `geographic_location_id`, `status`, `provenance`, `sort_order`, `created_by_user_id`, `updated_by_user_id`, `created_at`, `updated_at`'
                . ' FROM `projects` WHERE 1 = 1';
            if ($options['status'] !== null) {
                $sql .= ' AND `status` = :status';
            }
            if (array_key_exists('developer_id', $options)) {
                $sql .= $options['developer_id'] === null
                    ? ' AND `developer_id` IS NULL'
                    : ' AND `developer_id` = :developer_id';
            }
            if (array_key_exists('geographic_location_id', $options)) {
                $sql .= $options['geographic_location_id'] === null
                    ? ' AND `geographic_location_id` IS NULL'
                    : ' AND `geographic_location_id` = :geographic_location_id';
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
            if (array_key_exists('developer_id', $options) && $options['developer_id'] !== null) {
                $statement->bindValue(':developer_id', $options['developer_id'], PDO::PARAM_INT);
            }
            if (array_key_exists('geographic_location_id', $options) && $options['geographic_location_id'] !== null) {
                $statement->bindValue(':geographic_location_id', $options['geographic_location_id'], PDO::PARAM_INT);
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

        return array_map(fn (array $row): array => $this->mapProject($row), $rows);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return ProjectRecord
     */
    public function createProject(array $attributes): array
    {
        $this->writeFields($attributes, ['ulid', 'code', 'name_ar', 'name_en', 'developer_id', 'geographic_location_id', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id'], ['ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance']);
        $id = $this->queryBuilder->table('projects')->insert($attributes);
        $record = $this->findProjectById($id);
        if ($record === null) {
            throw new RuntimeException('Created Project could not be retrieved.');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $changes
     * @return ProjectRecord|null
     */
    public function updateProject(int|string $id, array $changes): ?array
    {
        $id = $this->identifier($id);
        $this->writeFields($changes, ['name_ar', 'name_en', 'developer_id', 'geographic_location_id', 'status', 'sort_order', 'updated_by_user_id']);
        if ($changes !== []) {
            $this->queryBuilder->table('projects')->where('id', '=', $id)
                ->update($changes);
        }

        return $this->findProjectById($id);
    }

    public function deleteProject(int|string $id): bool
    {
        $id = $this->identifier($id);

        return $this->queryBuilder->table('projects')->where('id', '=', $id)
            ->delete() > 0;
    }

    /**
     * @param array<string, mixed> $row
     * @return ProjectRecord
     */
    private function mapProject(array $row): array
    {
        return [
            'id' => $this->storedInteger($row['id']),
            'ulid' => (string) $row['ulid'],
            'code' => (string) $row['code'],
            'name_ar' => (string) $row['name_ar'],
            'name_en' => (string) $row['name_en'],
            'developer_id' => $row['developer_id'] === null ? null : $this->storedInteger($row['developer_id']),
            'geographic_location_id' => $row['geographic_location_id'] === null ? null : $this->storedInteger($row['geographic_location_id']),
            'status' => (string) $row['status'],
            'provenance' => (string) $row['provenance'],
            'sort_order' => $this->storedInteger($row['sort_order']),
            'created_by_user_id' => $row['created_by_user_id'] === null ? null : $this->storedInteger($row['created_by_user_id']),
            'updated_by_user_id' => $row['updated_by_user_id'] === null ? null : $this->storedInteger($row['updated_by_user_id']),
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    /** @return ProjectPhaseRecord|null */
    public function findProjectPhaseById(int|string $id): ?array
    {
        $id = $this->identifier($id);
        $row = $this->queryBuilder->table('project_phases')->select(self::PHASE_COLUMNS)
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : $this->mapProjectPhase($row);
    }

    /** @return ProjectPhaseRecord|null */
    public function findProjectPhaseByUlid(string $ulid): ?array
    {
        $row = $this->queryBuilder->table('project_phases')->select(self::PHASE_COLUMNS)
            ->where('ulid', '=', $ulid)
            ->first();

        return $row === null ? null : $this->mapProjectPhase($row);
    }

    /** @return ProjectPhaseRecord|null */
    public function findProjectPhaseByCode(int|string $projectId, string $code): ?array
    {
        $projectId = $this->identifier($projectId);
        $row = $this->queryBuilder->table('project_phases')->select(self::PHASE_COLUMNS)
            ->where('code', '=', $code)
            ->where('project_id', '=', $projectId)
            ->first();

        return $row === null ? null : $this->mapProjectPhase($row);
    }

    /** Advisory only; uniqueness remains enforced by the database. */
    public function projectPhaseExistsByCode(int|string $projectId, string $code): bool
    {
        return $this->findProjectPhaseByCode($projectId, $code) !== null;
    }

    /**
     * Nullable reference filters: omitted = unrestricted; null = IS NULL; numeric = equality.
     * @param array{status?: ?string, search?: string, limit?: int, offset?: int} $options
     * @return list<ProjectPhaseRecord>
     */
    public function listProjectPhases(int|string $projectId, array $options = []): array
    {
        $projectId = $this->identifier($projectId);
        $options = $this->listOptions($options, []);

        if ($options['search'] === '') {
            $query = $this->queryBuilder->table('project_phases')->select(self::PHASE_COLUMNS);
            $query->where('project_id', '=', $projectId);
            if ($options['status'] !== null) {
                $query->where('status', '=', $options['status']);
            }
            $rows = $query->orderBy('sort_order')->orderBy('id')
                ->limit($options['limit'])->offset($options['offset'])->get();
        } else {
            // QueryBuilder cannot express grouped search or IS NULL predicates.
            $sql = 'SELECT `id`, `ulid`, `project_id`, `code`, `name_ar`, `name_en`, `status`, `provenance`, `sort_order`, `created_by_user_id`, `updated_by_user_id`, `created_at`, `updated_at`'
                . ' FROM `project_phases` WHERE `project_id` = :project_id';
            if ($options['status'] !== null) {
                $sql .= ' AND `status` = :status';
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
            $statement->bindValue(':project_id', $projectId, PDO::PARAM_INT);
            if ($options['status'] !== null) {
                $statement->bindValue(':status', $options['status'], PDO::PARAM_STR);
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

        return array_map(fn (array $row): array => $this->mapProjectPhase($row), $rows);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return ProjectPhaseRecord
     */
    public function createProjectPhase(int|string $projectId, array $attributes): array
    {
        $projectId = $this->identifier($projectId);
        $this->writeFields($attributes, ['ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance', 'sort_order', 'created_by_user_id', 'updated_by_user_id'], ['ulid', 'code', 'name_ar', 'name_en', 'status', 'provenance']);
        $attributes['project_id'] = $projectId;
        $id = $this->queryBuilder->table('project_phases')->insert($attributes);
        $record = $this->findProjectPhaseById($id);
        if ($record === null) {
            throw new RuntimeException('Created ProjectPhase could not be retrieved.');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $changes
     * @return ProjectPhaseRecord|null
     */
    public function updateProjectPhase(int|string $projectId, int|string $phaseId, array $changes): ?array
    {
        $id = $this->identifier($phaseId);
        $projectId = $this->identifier($projectId);
        $this->writeFields($changes, ['name_ar', 'name_en', 'status', 'sort_order', 'updated_by_user_id']);
        if ($changes !== []) {
            $this->queryBuilder->table('project_phases')->where('id', '=', $id)
                ->where('project_id', '=', $projectId)
                ->update($changes);
        }

        $row = $this->queryBuilder->table('project_phases')->select(self::PHASE_COLUMNS)
            ->where('id', '=', $id)->where('project_id', '=', $projectId)->first();

        return $row === null ? null : $this->mapProjectPhase($row);
    }

    public function deleteProjectPhase(int|string $projectId, int|string $phaseId): bool
    {
        $id = $this->identifier($phaseId);
        $projectId = $this->identifier($projectId);

        return $this->queryBuilder->table('project_phases')->where('id', '=', $id)
            ->where('project_id', '=', $projectId)
            ->delete() > 0;
    }

    /**
     * @param array<string, mixed> $row
     * @return ProjectPhaseRecord
     */
    private function mapProjectPhase(array $row): array
    {
        return [
            'id' => $this->storedInteger($row['id']),
            'ulid' => (string) $row['ulid'],
            'project_id' => $this->storedInteger($row['project_id']),
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
