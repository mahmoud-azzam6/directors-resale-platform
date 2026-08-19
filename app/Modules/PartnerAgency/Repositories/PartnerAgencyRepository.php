<?php

declare(strict_types=1);

namespace App\Modules\PartnerAgency\Repositories;

use App\Core\Repository\BaseRepository;

/**
 * Persists Organization-backed Partner Agency records.
 */
final class PartnerAgencyRepository extends BaseRepository
{
    protected function table(): string
    {
        return 'organizations';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allActive(array $organizationIds = []): array
    {
        $query = $this->query()
            ->where('organization_type', '=', 'partner_agency')
            ->where('status', '!=', 'inactive');
        if ($organizationIds !== []) { $query->whereIn('id', $organizationIds); }
        return $query->get();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActive(int|string $id): ?array
    {
        return $this->query()
            ->where('id', '=', $id)
            ->where('organization_type', '=', 'partner_agency')
            ->where('status', '!=', 'inactive')
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findParent(int|string $id): ?array
    {
        return $this->query()
            ->where('id', '=', $id)
            ->first();
    }

    public function codeExists(string $code, int|string|null $ignoreId = null): bool
    {
        $query = $this->query()->where('code', '=', $code);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->first() !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function archive(int|string $id): ?array
    {
        $this->query()
            ->where('id', '=', $id)
            ->where('organization_type', '=', 'partner_agency')
            ->update(['status' => 'inactive']);

        return $this->query()
            ->where('id', '=', $id)
            ->where('organization_type', '=', 'partner_agency')
            ->first();
    }
}
