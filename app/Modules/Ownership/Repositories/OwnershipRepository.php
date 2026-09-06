<?php

declare(strict_types=1);

namespace App\Modules\Ownership\Repositories;

use App\Core\Database\QueryBuilderInterface;
use RuntimeException;

final class OwnershipRepository
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    public function create(array $attributes): array
    {
        $id = $this->queryBuilder->table('ownerships')->insert($attributes);
        $record = $this->findInOrganization($id, (int) $attributes['organization_id']);
        if ($record === null) {
            throw new RuntimeException('Created Ownership could not be retrieved.');
        }
        return $record;
    }

    /** @return array<string, mixed>|null */
    public function findInOrganization(int|string $id, int|string $organizationId): ?array
    {
        return $this->queryBuilder->table('ownerships')->where('id', '=', $id)
            ->where('organization_id', '=', $organizationId)->first();
    }

    /** @return array<string, mixed>|null */
    public function findForUpdate(int|string $id, int|string $organizationId): ?array
    {
        return $this->queryBuilder->table('ownerships')->where('id', '=', $id)
            ->where('organization_id', '=', $organizationId)->forUpdate()->first();
    }

    public function findOrganizationId(int|string $id): ?int
    {
        $record = $this->queryBuilder->table('ownerships')->select(['organization_id'])
            ->where('id', '=', $id)->first();

        return $record === null ? null : (int) $record['organization_id'];
    }

    /** @return array<string, mixed>|null */
    public function findCurrentForProperty(int|string $propertyId, int|string $organizationId): ?array
    {
        return $this->queryBuilder->table('ownerships')->where('organization_property_id', '=', $propertyId)
            ->where('organization_id', '=', $organizationId)->where('status', '=', 'current')->first();
    }

    /** @return array<string, mixed>|null */
    public function findCurrentForPropertyForUpdate(int|string $propertyId, int|string $organizationId): ?array
    {
        return $this->queryBuilder->table('ownerships')->where('organization_property_id', '=', $propertyId)
            ->where('organization_id', '=', $organizationId)->where('status', '=', 'current')
            ->forUpdate()->first();
    }

    /** @return array<int, array<string, mixed>> */
    public function historyForProperty(int|string $propertyId, int|string $organizationId): array
    {
        return $this->queryBuilder->table('ownerships')->where('organization_property_id', '=', $propertyId)
            ->where('organization_id', '=', $organizationId)->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->get();
    }

    public function currentExistsForProperty(int|string $propertyId, int|string $organizationId): bool
    {
        return $this->findCurrentForProperty($propertyId, $organizationId) !== null;
    }

    /** @return array<string, mixed>|null */
    public function closeCurrent(int|string $id, int|string $organizationId, string $closedAt, int|string $userId): ?array
    {
        $this->queryBuilder->table('ownerships')->where('id', '=', $id)
            ->where('organization_id', '=', $organizationId)->where('status', '=', 'current')->update([
                'status' => 'closed', 'closed_at' => $closedAt,
                'closed_by_user_id' => $userId, 'updated_by_user_id' => $userId,
            ]);
        return $this->findInOrganization($id, $organizationId);
    }
}
