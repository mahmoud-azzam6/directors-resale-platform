<?php

declare(strict_types=1);

namespace App\Modules\Property\Services;

use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\GeographicLocationRepository;
use PDOException;

final class GeographicLocationService
{
    private const RANKS = ['COUNTRY' => 1, 'GOVERNORATE' => 2, 'CITY' => 3, 'AREA' => 4, 'DISTRICT' => 5];

    public function __construct(
        private GeographicLocationRepository $locations,
        private DatabaseConnectionInterface $database,
        private UlidGeneratorInterface $ulids
    ) {
    }

    public function findLocation(int|string $id): ?array { return $this->locations->findGeographicLocationById($id); }
    public function listLocations(array $options = []): array { return $this->locations->listGeographicLocations($options); }
    public function listChildren(int|string $id, array $options = []): array { return $this->locations->listChildren($id, $options); }
    public function loadAncestry(int|string $id, int $depth = 32): array { return $this->locations->loadAncestry($id, $depth); }

    public function createLocation(array $data, int|string|null $actorId = null): array
    {
        $type = $this->type($data['location_type'] ?? null);
        if ($type === 'COUNTRY' && ($data['parent_id'] ?? null) !== null) { $this->fail('GEOGRAPHY_INVALID_PARENT_LEVEL'); }
        return $this->mutation(null, $data['parent_id'] ?? null, function (?array $target, array $locked) use ($data, $actorId): array {
            $type = $this->type($data['location_type'] ?? null);
            $parent = $this->parentFor($type, $data['parent_id'] ?? null, null, $locked);
            $attributes = $data;
            $attributes['ulid'] = $this->ulids->generate();
            $attributes['code'] = $this->code($data['code'] ?? null);
            $attributes['location_type'] = $type;
            $attributes['parent_id'] = $parent['id'] ?? null;
            $attributes['status'] = 'active';
            $attributes['provenance'] = $this->provenance($data['provenance'] ?? 'SYSTEM_ADMIN');
            unset($attributes['id'], $attributes['created_at'], $attributes['updated_at'], $attributes['created_by_user_id'], $attributes['updated_by_user_id']);
            $attributes['created_by_user_id'] = $this->actor($actorId);
            $attributes['updated_by_user_id'] = $this->actor($actorId);
            try { return $this->locations->createGeographicLocation($attributes); }
            catch (PDOException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { $this->fail('CATALOG_CODE_ALREADY_EXISTS'); }
                throw $exception;
            }
        });
    }

    public function updateLocation(int|string $id, array $data, int|string|null $actorId = null): ?array
    {
        $this->immutable($data);
        return $this->mutation($id, $data['parent_id'] ?? null, function (?array $target, array $locked) use ($data, $actorId): ?array {
            if ($target === null) { return null; }
            $parentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : $target['parent_id'];
            if (array_key_exists('parent_id', $data)) {
                $parent = $this->parentFor($target['location_type'], $parentId, $target['id'], $locked);
            }
            $changes = [];
            foreach (['name_ar', 'name_en', 'sort_order'] as $field) { if (array_key_exists($field, $data)) { $changes[$field] = $data[$field]; } }
            if (array_key_exists('parent_id', $data)) { $changes['parent_id'] = $parentId === null ? null : (int) $parentId; }
            $changes['updated_by_user_id'] = $this->actor($actorId);
            return $this->locations->updateGeographicLocation($target['id'], $changes);
        });
    }

    public function deactivateLocation(int|string $id, int|string|null $actorId = null): array
    {
        return $this->mutation($id, null, function (?array $target) use ($actorId): array {
            $target = $this->need($target, 'CATALOG_ITEM_INACTIVE');
            if ($target['status'] !== 'active') { $this->fail('CATALOG_ITEM_INACTIVE'); }
            if ($this->hasActiveDescendant($target['id'])) { $this->fail('LOCATION_HAS_ACTIVE_DESCENDANTS'); }
            return $this->need($this->locations->updateGeographicLocation($target['id'], ['status' => 'inactive', 'updated_by_user_id' => $this->actor($actorId)]), 'CATALOG_ITEM_INACTIVE');
        });
    }

    public function reactivateLocation(int|string $id, int|string|null $actorId = null): array
    {
        return $this->mutation($id, null, function (?array $target, array $locked) use ($actorId): array {
            $target = $this->need($target, 'CATALOG_ITEM_INACTIVE');
            if ($target['status'] !== 'inactive') { $this->fail('CATALOG_ITEM_INACTIVE'); }
            foreach ($this->lockedAncestry($target['id'], $locked) as $ancestor) { if ($ancestor['id'] !== $target['id'] && $ancestor['status'] !== 'active') { $this->fail('PARENT_CATALOG_INACTIVE'); } }
            return $this->need($this->locations->updateGeographicLocation($target['id'], ['status' => 'active', 'updated_by_user_id' => $this->actor($actorId)]), 'CATALOG_ITEM_INACTIVE');
        });
    }

    public function deleteLocation(int|string $id): bool
    {
        return $this->mutation($id, null, function (?array $target): bool {
            $target = $this->need($target, 'CATALOG_ITEM_INACTIVE');
            if ($target['provenance'] === 'SYSTEM_SEED') { $this->fail('SYSTEM_SEED_DELETE_FORBIDDEN'); }
            if ($this->locations->hasReferences($target['id'])) { $this->fail('CATALOG_ITEM_REFERENCED'); }
            return $this->locations->deleteGeographicLocation($target['id']);
        });
    }

    private function parentFor(string $type, mixed $parentId, ?int $targetId, array $locked): ?array
    {
        if ($type === 'COUNTRY') { if ($parentId !== null) { $this->fail('GEOGRAPHY_INVALID_PARENT_LEVEL'); } return null; }
        if ($parentId === null) { $this->fail('GEOGRAPHY_INVALID_PARENT_LEVEL'); }
        $parent = $locked[$this->id($parentId, 'GEOGRAPHY_INVALID_PARENT_LEVEL')] ?? null;
        if ($parent === null || $parent['status'] !== 'active') { $this->fail('PARENT_CATALOG_INACTIVE'); }
        if ($targetId !== null && $parent['id'] === $targetId) { $this->fail('GEOGRAPHY_SELF_PARENT'); }
        foreach ($this->lockedAncestry($parent['id'], $locked) as $ancestor) { if ($targetId !== null && $ancestor['id'] === $targetId) { $this->fail('GEOGRAPHY_CYCLE_DETECTED'); } }
        if (self::RANKS[$parent['location_type']] >= self::RANKS[$type]) { $this->fail('GEOGRAPHY_INVALID_PARENT_LEVEL'); }
        return $parent;
    }

    private function ancestry(int $id): array
    {
        $result = $this->locations->loadAncestry($id, 256);
        if ($result['stop_reason'] !== 'root') { $this->fail('GEOGRAPHY_CYCLE_DETECTED'); }
        return $result['locations'];
    }

    private function mutation(int|string|null $id, mixed $parentId, callable $callback): mixed
    {
        // Discovery precedes the transaction so its snapshot cannot leak into
        // descendant/reference checks after the serialization locks are held.
        $retry = new \RuntimeException('Geography changed during root discovery.');
        while (true) {
            $candidate = $id === null ? null : $this->locations->findGeographicLocationById($id);
            $starts = $candidate === null ? [] : [$candidate['id']];
            if ($parentId !== null) { $starts[] = $this->id($parentId, 'GEOGRAPHY_INVALID_PARENT_LEVEL'); }
            $roots = []; $rows = []; $paths = [];
            foreach (array_unique($starts) as $start) {
                if ($this->locations->findGeographicLocationById($start) === null) { continue; }
                $path = $this->ancestry($start);
                $root = $path[array_key_last($path)]['id'];
                $roots[$root] = $root;
                $paths[$start] = $root;
                foreach ($path as $row) { $rows[$row['id']] = $row['id']; }
            }
            sort($roots, SORT_NUMERIC); sort($rows, SORT_NUMERIC);
            try {
                return $this->database->transaction(function () use ($candidate, $roots, $rows, $paths, $callback, $retry): mixed {
                    $locked = [];
                    foreach ($roots as $root) {
                        $record = $this->locations->findGeographicLocationForUpdate($root);
                        if ($record === null || $record['parent_id'] !== null) { throw $retry; }
                        $locked[$root] = $record;
                    }
                    $target = $candidate === null ? null : $this->locations->findGeographicLocationForUpdate($candidate['id']);
                    if ($candidate !== null && $target === null) { throw $retry; }
                    if ($target !== null) { $locked[$target['id']] = $target; }
                    foreach ($rows as $row) {
                        if (!isset($locked[$row])) { $locked[$row] = $this->locations->findGeographicLocationForUpdate($row); }
                    }
                    foreach ($paths as $start => $root) {
                        $path = $this->lockedAncestry($start, $locked, $retry);
                        if ($path[array_key_last($path)]['id'] !== $root) { throw $retry; }
                    }
                    return $callback($target, $locked);
                });
            } catch (\RuntimeException $exception) {
                if ($exception !== $retry) { throw $exception; }
                // Release every lock before rediscovering a changed root/path.
            }
        }
    }

    private function lockedAncestry(int $id, array $locked, ?\RuntimeException $retry = null): array
    {
        $path = []; $visited = [];
        while (true) {
            if (isset($visited[$id]) || count($path) >= 256) { $this->fail('GEOGRAPHY_CYCLE_DETECTED'); }
            $row = $locked[$id] ?? null;
            if ($row === null) {
                if ($retry !== null) { throw $retry; }
                $this->fail('GEOGRAPHY_CYCLE_DETECTED');
            }
            $path[] = $row; $visited[$id] = true;
            if ($row['parent_id'] === null) { return $path; }
            $id = $row['parent_id'];
        }
    }

    private function hasActiveDescendant(int $id): bool
    {
        $pending = [$id];
        while ($pending !== []) {
            $parent = array_pop($pending);
            $offset = 0;
            do {
                $children = $this->locations->listChildren($parent, ['limit' => 50, 'offset' => $offset]);
                foreach ($children as $child) {
                    if ($child['status'] === 'active') { return true; }
                    $pending[] = $child['id'];
                }
                $offset += count($children);
            } while (count($children) === 50);
        }
        return false;
    }

    private function type(mixed $type): string { if (!is_string($type) || !isset(self::RANKS[$type])) { $this->fail('GEOGRAPHY_INVALID_PARENT_LEVEL'); } return $type; }
    private function code(mixed $code): string { if (!is_string($code) || ($code = strtoupper(trim($code))) === '') { $this->fail('CATALOG_CODE_ALREADY_EXISTS'); } return $code; }
    private function provenance(mixed $value): string { if (!is_string($value) || !in_array($value, ['SYSTEM_ADMIN', 'SYSTEM_SEED'], true)) { $this->fail('CATALOG_ITEM_INACTIVE'); } return $value; }
    private function actor(int|string|null $id): ?int { return $id === null ? null : $this->id($id, 'CATALOG_ITEM_INACTIVE'); }
    private function id(mixed $id, string $error): int { if ((!is_int($id) && (!is_string($id) || !ctype_digit($id))) || (int)$id <= 0) { $this->fail($error); } return (int)$id; }
    private function immutable(array $data): void { foreach (['id','ulid','code','location_type','type','provenance','status','created_by_user_id','updated_by_user_id'] as $field) { if (array_key_exists($field,$data)) { $this->fail('CATALOG_IDENTITY_IMMUTABLE'); } } }
    private function need(?array $record, string $error): array { if ($record === null) { $this->fail($error); } return $record; }
    private function fail(string $code): never { throw new ValidationException([$code => $code]); }
}
