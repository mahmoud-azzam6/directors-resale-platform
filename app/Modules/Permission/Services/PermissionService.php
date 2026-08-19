<?php

declare(strict_types=1);

namespace App\Modules\Permission\Services;

use App\Modules\Permission\Models\Permission;
use App\Modules\Permission\Repositories\PermissionRepository;

final class PermissionService
{
    public function __construct(private PermissionRepository $repository) {}

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return array_map(static fn (array $row): array => Permission::fromArray($row)->toArray(), $this->repository->allActive());
    }
}