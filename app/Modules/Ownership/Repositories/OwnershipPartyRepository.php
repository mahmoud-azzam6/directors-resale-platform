<?php

declare(strict_types=1);

namespace App\Modules\Ownership\Repositories;

use App\Core\Database\QueryBuilderInterface;
use RuntimeException;

final class OwnershipPartyRepository
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    public function create(array $attributes): array
    {
        $id = $this->queryBuilder->table('ownership_parties')->insert($attributes);
        $record = $this->find($id, (int) $attributes['ownership_id'], (int) $attributes['organization_id']);
        if ($record === null) {
            throw new RuntimeException('Created Ownership Party could not be retrieved.');
        }
        return $record;
    }

    /** @param array<int, array<string, mixed>> $parties @return array<int, array<string, mixed>> */
    public function createMany(array $parties): array
    {
        return array_map(fn (array $party): array => $this->create($party), $parties);
    }

    /** @return array<string, mixed>|null */
    public function find(int|string $id, int|string $ownershipId, int|string $organizationId): ?array
    {
        return $this->queryBuilder->table('ownership_parties')->where('id', '=', $id)
            ->where('ownership_id', '=', $ownershipId)->where('organization_id', '=', $organizationId)->first();
    }

    /** @return array<int, array<string, mixed>> */
    public function forOwnership(int|string $ownershipId, int|string $organizationId): array
    {
        return $this->queryBuilder->table('ownership_parties')->where('ownership_id', '=', $ownershipId)
            ->where('organization_id', '=', $organizationId)->orderBy('id')->get();
    }

    public function ownerExists(int|string $ownershipId, int|string $ownerId, int|string $organizationId): bool
    {
        return $this->queryBuilder->table('ownership_parties')->where('ownership_id', '=', $ownershipId)
            ->where('owner_id', '=', $ownerId)->where('organization_id', '=', $organizationId)->first() !== null;
    }

    /** @return array<string, mixed>|null */
    public function updateShare(int|string $id, int|string $ownershipId, int|string $organizationId, mixed $share, int|string $userId): ?array
    {
        $this->queryBuilder->table('ownership_parties')->where('id', '=', $id)
            ->where('ownership_id', '=', $ownershipId)->where('organization_id', '=', $organizationId)
            ->update(['share_percentage' => $share, 'updated_by_user_id' => $userId]);
        return $this->find($id, $ownershipId, $organizationId);
    }

    public function remove(int|string $id, int|string $ownershipId, int|string $organizationId): bool
    {
        return $this->queryBuilder->table('ownership_parties')->where('id', '=', $id)
            ->where('ownership_id', '=', $ownershipId)->where('organization_id', '=', $organizationId)->delete() > 0;
    }
}
