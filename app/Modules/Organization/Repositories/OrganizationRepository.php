<?php

declare(strict_types=1);

namespace App\Modules\Organization\Repositories;

use App\Core\Repository\BaseRepository;

/**
 * Persists organizations using the shared repository foundation.
 */
final class OrganizationRepository extends BaseRepository
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
     * @return array<string, mixed>|null
     */
    public function findByCode(string $code, int|string|null $ignoreId = null): ?array
    {
        $query = $this->query()->where('code', '=', $code);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->first();
    }

    public function codeExists(string $code, int|string|null $ignoreId = null): bool
    {
        return $this->findByCode($code, $ignoreId) !== null;
    }
}
