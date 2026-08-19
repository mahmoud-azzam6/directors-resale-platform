<?php

declare(strict_types=1);

namespace App\Modules\Permission\Services;

use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Permission\Repositories\PermissionRepository;
use App\Modules\Permission\Repositories\PositionPermissionRepository;
use App\Modules\Position\Repositories\PositionRepository;

final class PositionPermissionService
{
    public function __construct(
        private PositionRepository $positionRepository,
        private PermissionRepository $permissionRepository,
        private PositionPermissionRepository $assignmentRepository,
        private DatabaseConnectionInterface $database
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function list(int|string $positionId): array
    {
        $rows = $this->assignmentRepository->forPosition($positionId);
        $result = [];
        foreach ($rows as $row) {
            $permission = $this->permissionRepository->findActive((int) $row['permission_id']);
            if ($permission !== null) { $result[] = $permission; }
        }
        return $result;
    }

    /** @param array<int, mixed> $requested */
    public function replace(int|string $positionId, array $requested): array
    {
        $position = $this->positionRepository->findActive($positionId);
        if ($position === null) { throw new ValidationException(['position_id' => 'Position must exist and be active.']); }

        $ids = [];
        $codes = [];
        foreach ($requested as $value) {
            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                $permission = $this->permissionRepository->findActive((int) $value);
            } elseif (is_string($value)) {
                $permission = $this->permissionRepository->findActiveByCode($value);
            } else { $permission = null; }
            if ($permission === null) { throw new ValidationException(['permissions' => 'All permissions must be active and known.']); }
            $id = (int) $permission['id'];
            if (isset($ids[$id])) { continue; }
            $ids[$id] = true; $codes[] = $id;
        }

        $this->database->transaction(function () use ($positionId, $codes): void {
            $this->assignmentRepository->removeForPosition($positionId);
            foreach ($codes as $permissionId) { $this->assignmentRepository->assign((int) $positionId, $permissionId); }
        });

        return $this->list($positionId);
    }
}