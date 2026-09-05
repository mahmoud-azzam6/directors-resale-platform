<?php

declare(strict_types=1);

namespace App\Modules\GlobalPropertyIdentity\Repositories;

use App\Core\Database\QueryBuilderInterface;
use RuntimeException;

final class GlobalPhysicalIdentityLinkRepository
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    public function create(array $attributes): array
    {
        $id = $this->queryBuilder->table('global_physical_identity_links')->insert($attributes);
        $record = $this->find($id);
        if ($record === null) {
            throw new RuntimeException('Created Global Physical Identity link could not be retrieved.');
        }
        return $record;
    }

    /** @return array<string, mixed>|null */
    public function find(int|string $id): ?array
    {
        return $this->queryBuilder->table('global_physical_identity_links')->where('id', '=', $id)->first();
    }

    /** @return array<string, mixed>|null */
    public function findActiveForProperty(int|string $propertyId): ?array
    {
        return $this->queryBuilder->table('global_physical_identity_links')
            ->where('active_property_guard', '=', $propertyId)->first();
    }

    /** @return array<string, mixed>|null */
    public function findActiveForPropertyForUpdate(int|string $propertyId): ?array
    {
        return $this->queryBuilder->table('global_physical_identity_links')
            ->where('active_property_guard', '=', $propertyId)->forUpdate()->first();
    }

    /** @return array<int, array<string, mixed>> */
    public function historyForProperty(int|string $propertyId): array
    {
        return $this->queryBuilder->table('global_physical_identity_links')
            ->where('organization_property_id', '=', $propertyId)->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->get();
    }

    /** @return array<int, array<string, mixed>> */
    public function representationsForIdentity(int|string $identityId): array
    {
        return $this->queryBuilder->table('global_physical_identity_links')
            ->where('global_physical_property_identity_id', '=', $identityId)->orderBy('created_at', 'DESC')->get();
    }

    /** @return array<string, mixed>|null */
    public function unlink(
        int|string $linkId,
        int|string $propertyId,
        string $unlinkedAt,
        int|string $userId,
        string $reason
    ): ?array
    {
        $this->queryBuilder->table('global_physical_identity_links')
            ->where('id', '=', $linkId)
            ->where('organization_property_id', '=', $propertyId)
            ->where('active_property_guard', '=', $propertyId)
            ->update([
                'unlinked_at' => $unlinkedAt, 'unlinked_by_user_id' => $userId,
                'unlink_reason' => $reason, 'updated_by_user_id' => $userId,
            ]);

        return $this->find($linkId);
    }
}
