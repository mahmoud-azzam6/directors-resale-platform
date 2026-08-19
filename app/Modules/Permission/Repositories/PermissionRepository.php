<?php

declare(strict_types=1);

namespace App\Modules\Permission\Repositories;

use App\Core\Repository\BaseRepository;

final class PermissionRepository extends BaseRepository
{
    protected function table(): string { return 'permissions'; }
    protected function primaryKey(): string { return 'id'; }

    /** @return array<int, array<string, mixed>> */
    public function allActive(): array { return $this->query()->where('status', '!=', 'inactive')->get(); }

    /** @return array<string, mixed>|null */
    public function findActive(int|string $id): ?array
    { return $this->query()->where('id', '=', $id)->where('status', '!=', 'inactive')->first(); }

    /** @return array<string, mixed>|null */
    public function findActiveByCode(string $code): ?array
    { return $this->query()->where('code', '=', $code)->where('status', '!=', 'inactive')->first(); }
}