<?php

declare(strict_types=1);

namespace App\Modules\Property\Services;

use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\DevelopmentCatalogRepository;
use App\Modules\Property\Repositories\GeographicLocationRepository;
use PDOException;
use RuntimeException;

/** Development catalog policy and transaction ownership; no HTTP or authorization. */
final class DevelopmentCatalogService
{
    public function __construct(
        private DevelopmentCatalogRepository $catalogs,
        private GeographicLocationRepository $locations,
        private DatabaseConnectionInterface $database,
        private UlidGeneratorInterface $ulids
    ) {
    }

    public function findDeveloper(int|string $id): ?array { return $this->catalogs->findDeveloperById($id); }
    public function listDevelopers(array $options = []): array { return $this->catalogs->listDevelopers($options); }
    public function findProject(int|string $id): ?array { return $this->catalogs->findProjectById($id); }
    public function listProjects(array $options = []): array { return $this->catalogs->listProjects($options); }
    public function findProjectPhase(int|string $id): ?array { return $this->catalogs->findProjectPhaseById($id); }
    public function listProjectPhases(int|string $projectId, array $options = []): array { return $this->catalogs->listProjectPhases($projectId, $options); }

    public function createDeveloper(array $data, int|string|null $actorId = null): array
    {
        return $this->database->transaction(fn (): array => $this->createCatalog($data, $actorId, fn (array $attributes): array => $this->catalogs->createDeveloper($attributes)));
    }

    public function updateDeveloper(int|string $id, array $data, int|string|null $actorId = null): ?array
    {
        $this->immutable($data);
        return $this->database->transaction(function () use ($id, $data, $actorId): ?array {
            $record = $this->catalogs->findDeveloperForUpdate($id);
            if ($record === null) { return null; }
            return $this->catalogs->updateDeveloper($record['id'], $this->editable($data, $actorId));
        });
    }

    public function deactivateDeveloper(int|string $id, int|string|null $actorId = null): array
    {
        return $this->changeDeveloperStatus($id, 'active', 'inactive', $actorId);
    }

    public function reactivateDeveloper(int|string $id, int|string|null $actorId = null): array
    {
        return $this->changeDeveloperStatus($id, 'inactive', 'active', $actorId);
    }

    private function changeDeveloperStatus(int|string $id, string $from, string $to, int|string|null $actorId): array
    {
        return $this->database->transaction(function () use ($id, $from, $to, $actorId): array {
            $record = $this->required($this->catalogs->findDeveloperForUpdate($id));
            $this->requireStatus($record, $from);
            if ($to === 'inactive' && $this->catalogs->hasProjectsForDeveloper($record['id'], 'active')) { $this->fail('DEVELOPER_HAS_ACTIVE_PROJECTS'); }
            return $this->required($this->catalogs->updateDeveloper($record['id'], $this->status($to, $actorId)));
        });
    }

    public function deleteDeveloper(int|string $id): bool
    {
        return $this->database->transaction(function () use ($id): bool {
            $record = $this->required($this->catalogs->findDeveloperForUpdate($id));
            $this->deletable($record);
            if ($this->catalogs->hasProjectsForDeveloper($record['id'])) { $this->fail('CATALOG_ITEM_REFERENCED'); }
            return $this->deleteCatalog(fn (): bool => $this->catalogs->deleteDeveloper($record['id']));
        });
    }

    public function createProject(array $data, int|string|null $actorId = null): array
    {
        $developerId = $this->optionalId($data['developer_id'] ?? null);
        $locationId = $this->optionalId($data['geographic_location_id'] ?? null);
        return $this->database->transaction(function () use ($data, $actorId, $developerId, $locationId): array {
            if ($developerId !== null) { $this->activeParent($this->catalogs->findDeveloperForUpdate($developerId)); }
            if ($locationId !== null) { $this->activeParent($this->locations->findGeographicLocationForUpdate($locationId)); }
            $data['developer_id'] = $developerId;
            $data['geographic_location_id'] = $locationId;
            return $this->createCatalog($data, $actorId, fn (array $attributes): array => $this->catalogs->createProject($attributes));
        });
    }

    public function updateProject(int|string $id, array $data, int|string|null $actorId = null): ?array
    {
        $this->immutable($data);
        return $this->projectMutation($id, $data, function (?array $record, array $developers, ?array $location) use ($data, $actorId): ?array {
            if ($record === null) { return null; }
            $changes = $this->editable($data, $actorId);
            foreach (['developer_id', 'geographic_location_id'] as $field) {
                if (!array_key_exists($field, $data)) { continue; }
                $newId = $this->optionalId($data[$field]);
                if ($newId !== null && $newId !== $record[$field]) {
                    $this->activeParent($field === 'developer_id' ? ($developers[$newId] ?? null) : $location);
                }
                $changes[$field] = $newId;
            }
            return $this->catalogs->updateProject($record['id'], $changes);
        });
    }

    public function deactivateProject(int|string $id, int|string|null $actorId = null): array
    {
        return $this->changeProjectStatus($id, 'active', 'inactive', $actorId);
    }

    public function reactivateProject(int|string $id, int|string|null $actorId = null): array
    {
        return $this->changeProjectStatus($id, 'inactive', 'active', $actorId);
    }

    private function changeProjectStatus(int|string $id, string $from, string $to, int|string|null $actorId): array
    {
        return $this->projectMutation($id, [], function (?array $record, array $developers) use ($from, $to, $actorId): array {
            $record = $this->required($record);
            $this->requireStatus($record, $from);
            if ($to === 'inactive' && $this->catalogs->hasPhasesForProject($record['id'], 'active')) { $this->fail('PROJECT_HAS_ACTIVE_PHASES'); }
            if ($to === 'active' && $record['developer_id'] !== null) { $this->activeParent($developers[$record['developer_id']] ?? null); }
            // Geography is an assignment reference, never a lifecycle parent.
            return $this->required($this->catalogs->updateProject($record['id'], $this->status($to, $actorId)));
        });
    }

    public function deleteProject(int|string $id): bool
    {
        return $this->projectMutation($id, [], function (?array $record): bool {
            $record = $this->required($record);
            $this->deletable($record);
            if ($this->catalogs->hasPhasesForProject($record['id'])) { $this->fail('CATALOG_ITEM_REFERENCED'); }
            return $this->deleteCatalog(fn (): bool => $this->catalogs->deleteProject($record['id']));
        });
    }

    /** Discovery is advisory; the locked Project determines every mutation decision. */
    private function projectMutation(int|string $id, array $data, callable $callback): mixed
    {
        $newDeveloperId = $this->optionalId($data['developer_id'] ?? null);
        $locationId = $this->optionalId($data['geographic_location_id'] ?? null);
        $retry = new RuntimeException('Project Developer changed during lock discovery.');
        while (true) {
            $candidate = $this->catalogs->findProjectById($id);
            $oldDeveloperId = $candidate['developer_id'] ?? null;
            $ids = array_values(array_unique(array_filter([$oldDeveloperId, $newDeveloperId], fn ($value): bool => $value !== null)));
            sort($ids, SORT_NUMERIC);
            try {
                return $this->database->transaction(function () use ($id, $ids, $oldDeveloperId, $locationId, $callback, $retry): mixed {
                    $developers = [];
                    foreach ($ids as $developerId) { $developers[$developerId] = $this->catalogs->findDeveloperForUpdate($developerId); }
                    // Acquire assignment-reference locks before the Project as well.
                    // No Geography hierarchy mutation or root traversal belongs here.
                    $location = $locationId === null ? null : $this->locations->findGeographicLocationForUpdate($locationId);
                    $record = $this->catalogs->findProjectForUpdate($id);
                    if ($record !== null && $record['developer_id'] !== $oldDeveloperId) { throw $retry; }
                    return $callback($record, $developers, $location);
                });
            } catch (RuntimeException $exception) {
                if ($exception !== $retry) { throw $exception; }
                // Roll back before discovering and locking a different Developer.
            }
        }
    }

    public function createProjectPhase(int|string $projectId, array $data, int|string|null $actorId = null): array
    {
        $projectId = $this->requiredId($projectId, 'PARENT_CATALOG_INACTIVE');
        // The parent is supplied by the scoped operation, never by mutable data.
        if (array_key_exists('project_id', $data)) { $this->fail('CATALOG_IDENTITY_IMMUTABLE'); }
        return $this->database->transaction(function () use ($projectId, $data, $actorId): array {
            $this->activeParent($this->catalogs->findProjectForUpdate($projectId));
            return $this->createCatalog($data, $actorId, fn (array $attributes): array => $this->catalogs->createProjectPhase($projectId, $attributes));
        });
    }

    public function updateProjectPhase(int|string $projectId, int|string $phaseId, array $data, int|string|null $actorId = null): ?array
    {
        $this->immutable($data, ['project_id']);
        return $this->phaseMutation($projectId, $phaseId, function (?array $record) use ($data, $actorId): ?array {
            if ($record === null) { return null; }
            return $this->catalogs->updateProjectPhase($record['project_id'], $record['id'], $this->editable($data, $actorId));
        });
    }

    public function deactivateProjectPhase(int|string $projectId, int|string $phaseId, int|string|null $actorId = null): array
    {
        return $this->changePhaseStatus($projectId, $phaseId, 'active', 'inactive', $actorId);
    }

    public function reactivateProjectPhase(int|string $projectId, int|string $phaseId, int|string|null $actorId = null): array
    {
        return $this->changePhaseStatus($projectId, $phaseId, 'inactive', 'active', $actorId);
    }

    private function changePhaseStatus(int|string $projectId, int|string $phaseId, string $from, string $to, int|string|null $actorId): array
    {
        return $this->phaseMutation($projectId, $phaseId, function (?array $record, ?array $project) use ($from, $to, $actorId): array {
            $record = $this->required($record);
            $this->requireStatus($record, $from);
            if ($to === 'active') { $this->activeParent($project); }
            return $this->required($this->catalogs->updateProjectPhase($record['project_id'], $record['id'], $this->status($to, $actorId)));
        });
    }

    public function deleteProjectPhase(int|string $projectId, int|string $phaseId): bool
    {
        return $this->phaseMutation($projectId, $phaseId, function (?array $record): bool {
            $record = $this->required($record);
            $this->deletable($record);
            // The current schema has no inbound Phase references. RESTRICT
            // remains the final defense if a referenced delete is attempted.
            return $this->deleteCatalog(fn (): bool => $this->catalogs->deleteProjectPhase($record['project_id'], $record['id']));
        });
    }

    private function phaseMutation(int|string $projectId, int|string $phaseId, callable $callback): mixed
    {
        return $this->database->transaction(function () use ($projectId, $phaseId, $callback): mixed {
            $project = $this->catalogs->findProjectForUpdate($projectId);
            $phase = $this->catalogs->findProjectPhaseForUpdate($projectId, $phaseId);
            return $callback($phase, $project);
        });
    }

    private function createCatalog(array $data, int|string|null $actorId, callable $create): array
    {
        $code = $data['code'] ?? null;
        if (!is_string($code) || ($code = strtoupper(trim($code))) === '') { $this->fail('CATALOG_CODE_ALREADY_EXISTS'); }
        $provenance = $data['provenance'] ?? 'SYSTEM_ADMIN';
        if (!in_array($provenance, ['SYSTEM_ADMIN', 'SYSTEM_SEED'], true)) { $this->fail('CATALOG_ITEM_INACTIVE'); }
        $attributes = $data;
        $attributes['code'] = $code;
        $attributes['ulid'] = $this->ulids->generate();
        $attributes['status'] = 'active';
        $attributes['provenance'] = $provenance;
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at'], $attributes['created_by_user_id'], $attributes['updated_by_user_id']);
        $attributes['created_by_user_id'] = $this->actor($actorId);
        $attributes['updated_by_user_id'] = $this->actor($actorId);
        try { return $create($attributes); }
        catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { $this->fail('CATALOG_CODE_ALREADY_EXISTS'); }
            throw $exception;
        }
    }

    private function editable(array $data, int|string|null $actorId): array
    {
        $changes = [];
        foreach (['name_ar', 'name_en', 'sort_order'] as $field) { if (array_key_exists($field, $data)) { $changes[$field] = $data[$field]; } }
        $changes['updated_by_user_id'] = $this->actor($actorId);
        return $changes;
    }

    private function immutable(array $data, array $additional = []): void
    {
        foreach (array_merge(['id', 'ulid', 'code', 'provenance', 'status', 'created_by_user_id', 'updated_by_user_id'], $additional) as $field) {
            if (array_key_exists($field, $data)) { $this->fail('CATALOG_IDENTITY_IMMUTABLE'); }
        }
    }

    private function deleteCatalog(callable $delete): bool
    {
        try { return $delete(); }
        catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1451) { $this->fail('CATALOG_ITEM_REFERENCED'); }
            throw $exception;
        }
    }

    private function deletable(array $record): void
    {
        if ($record['provenance'] === 'SYSTEM_SEED') { $this->fail('SYSTEM_SEED_DELETE_FORBIDDEN'); }
        if ($record['provenance'] !== 'SYSTEM_ADMIN') { $this->fail('CATALOG_ITEM_REFERENCED'); }
    }

    private function activeParent(?array $record): void
    {
        if ($record === null || $record['status'] !== 'active') { $this->fail('PARENT_CATALOG_INACTIVE'); }
    }

    private function requireStatus(array $record, string $status): void { if ($record['status'] !== $status) { $this->fail('CATALOG_ITEM_INACTIVE'); } }
    private function required(?array $record): array { if ($record === null) { $this->fail('CATALOG_ITEM_INACTIVE'); } return $record; }
    private function status(string $status, int|string|null $actorId): array { return ['status' => $status, 'updated_by_user_id' => $this->actor($actorId)]; }
    private function actor(int|string|null $id): ?int { return $id === null ? null : $this->requiredId($id); }
    private function optionalId(mixed $id): ?int { return $id === null ? null : $this->requiredId($id, 'PARENT_CATALOG_INACTIVE'); }
    private function requiredId(mixed $id, string $error = 'CATALOG_ITEM_INACTIVE'): int
    {
        if ((!is_int($id) && (!is_string($id) || !ctype_digit($id))) || (int) $id <= 0) { $this->fail($error); }
        $digits = ltrim((string) $id, '0');
        $maximum = (string) PHP_INT_MAX;
        if (strlen($digits) > strlen($maximum) || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) { $this->fail($error); }
        return (int) $id;
    }
    private function fail(string $code): never { throw new ValidationException([$code => $code]); }
}
