<?php

declare(strict_types=1);

namespace App\Modules\Position\Repositories;

use App\Core\Repository\BaseRepository;

/**
 * Persists dynamic Position records.
 */
final class PositionRepository extends BaseRepository
{
    protected function table(): string
    {
        return 'positions';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @return array<int, array<string, mixed>> */
    public function allActive(array $organizationIds = []): array
    {
        $query = $this->query()->where('status', '!=', 'inactive');
        if ($organizationIds !== []) { $query->whereIn('organization_id', $organizationIds); }
        return $query->get();
    }

    /** @return array<string, mixed>|null */
    public function findActive(int|string $id): ?array
    {
        return $this->query()
            ->where('id', '=', $id)
            ->where('status', '!=', 'inactive')
            ->first();
    }

    public function codeExists(int|string $organizationId, string $code, int|string|null $ignoreId = null): bool
    {
        $query = $this->query()
            ->where('organization_id', '=', $organizationId)
            ->where('code', '=', $code);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->first() !== null;
    }

    /** @return array<string, mixed>|null */
    public function deactivate(int|string $id): ?array
    {
        $this->query()->where('id', '=', $id)->update(['status' => 'inactive']);

        return $this->find($id);
    }
}