<?php

declare(strict_types=1);

namespace App\Modules\Permission\Repositories;

use App\Core\Repository\BaseRepository;

final class PositionPermissionRepository extends BaseRepository
{
    protected function table(): string { return 'position_permissions'; }
    protected function primaryKey(): string { return 'id'; }

    /** @return array<int, array<string, mixed>> */
    public function forPosition(int|string $positionId): array
    { return $this->query()->where('position_id', '=', $positionId)->get(); }

    /** @return array<int, array<string, mixed>> */
    public function activePermissionsForPosition(int|string $positionId): array
    {
        return $this->query()->where('position_id', '=', $positionId)->get();
    }

    public function assign(int $positionId, int $permissionId): void
    { $this->query()->insert(['position_id' => $positionId, 'permission_id' => $permissionId]); }

    public function removeForPosition(int|string $positionId): void
    { $this->query()->where('position_id', '=', $positionId)->delete(); }
}