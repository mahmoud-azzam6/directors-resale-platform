<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;

/**
 * Persists staged User records.
 */
final class UserRepository extends BaseRepository
{
    protected function table(): string
    {
        return 'users';
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

    /** @return array<int, array<string, mixed>> */
    public function allForAdministration(array $organizationIds): array
    {
        if ($organizationIds === []) {
            return [];
        }

        return $this->query()
            ->whereIn('organization_id', $organizationIds)
            ->get();
    }

    /** @return array<string, mixed>|null */
    public function findActive(int|string $id): ?array
    {
        return $this->query()
            ->where('id', '=', $id)
            ->where('status', '!=', 'inactive')
            ->first();
    }

    public function emailExists(string $email, int|string|null $ignoreId = null): bool
    {
        $query = $this->query()->where('email', '=', $email);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->first() !== null;
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        return $this->query()->where('email', '=', $email)->first();
    }

    public function setPasswordHash(int|string $id, string $passwordHash): bool
    {
        return $this->query()
            ->where('id', '=', $id)
            ->update(['password_hash' => $passwordHash]) > 0;
    }

    /** @return array<string, mixed>|null */
    public function deactivate(int|string $id): ?array
    {
        $this->query()->where('id', '=', $id)->update(['status' => 'inactive']);

        return $this->find($id);
    }
}
