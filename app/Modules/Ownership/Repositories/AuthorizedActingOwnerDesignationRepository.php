<?php

declare(strict_types=1);

namespace App\Modules\Ownership\Repositories;

use App\Core\Database\QueryBuilderInterface;
use RuntimeException;

final class AuthorizedActingOwnerDesignationRepository
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    public function create(array $attributes): array
    {
        $id = $this->queryBuilder->table('authorized_acting_owner_designations')->insert($attributes);
        $record = $this->find($id, (int) $attributes['ownership_id']);
        if ($record === null) {
            throw new RuntimeException('Created Acting Owner designation could not be retrieved.');
        }
        return $record;
    }

    /** @return array<string, mixed>|null */
    public function find(int|string $id, int|string $ownershipId): ?array
    {
        return $this->queryBuilder->table('authorized_acting_owner_designations')->where('id', '=', $id)
            ->where('ownership_id', '=', $ownershipId)->first();
    }

    /** @return array<string, mixed>|null */
    public function findCurrent(int|string $ownershipId): ?array
    {
        return $this->queryBuilder->table('authorized_acting_owner_designations')
            ->where('current_ownership_guard', '=', $ownershipId)->first();
    }

    /** @return array<string, mixed>|null */
    public function findCurrentForUpdate(int|string $ownershipId): ?array
    {
        return $this->queryBuilder->table('authorized_acting_owner_designations')
            ->where('current_ownership_guard', '=', $ownershipId)->forUpdate()->first();
    }

    /** @return array<int, array<string, mixed>> */
    public function historyForOwnership(int|string $ownershipId): array
    {
        return $this->queryBuilder->table('authorized_acting_owner_designations')
            ->where('ownership_id', '=', $ownershipId)->orderBy('started_at', 'DESC')->orderBy('id', 'DESC')->get();
    }

    /** @return array<string, mixed>|null */
    public function end(
        int|string $designationId,
        int|string $ownershipId,
        string $endedAt,
        int|string $userId
    ): ?array
    {
        $this->queryBuilder->table('authorized_acting_owner_designations')
            ->where('id', '=', $designationId)
            ->where('ownership_id', '=', $ownershipId)
            ->where('current_ownership_guard', '=', $ownershipId)
            ->update([
                'ended_at' => $endedAt, 'ended_by_user_id' => $userId, 'updated_by_user_id' => $userId,
            ]);

        return $this->find($designationId, $ownershipId);
    }
}
