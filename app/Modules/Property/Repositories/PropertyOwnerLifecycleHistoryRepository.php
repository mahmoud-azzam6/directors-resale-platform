<?php

declare(strict_types=1);

namespace App\Modules\Property\Repositories;

use App\Core\Database\QueryBuilderInterface;
use RuntimeException;

final class PropertyOwnerLifecycleHistoryRepository
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    public function appendPropertyEvent(array $attributes): array
    {
        $attributes['owner_id'] = null;
        return $this->append($attributes);
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    public function appendOwnerEvent(array $attributes): array
    {
        $attributes['organization_property_id'] = null;
        return $this->append($attributes);
    }

    /** @return array<int, array<string, mixed>> */
    public function forProperty(int|string $propertyId, int|string $organizationId): array
    {
        return $this->queryBuilder->table('property_owner_lifecycle_history')
            ->where('organization_property_id', '=', $propertyId)->where('organization_id', '=', $organizationId)
            ->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->get();
    }

    /** @return array<int, array<string, mixed>> */
    public function forOwner(int|string $ownerId, int|string $organizationId): array
    {
        return $this->queryBuilder->table('property_owner_lifecycle_history')
            ->where('owner_id', '=', $ownerId)->where('organization_id', '=', $organizationId)
            ->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->get();
    }

    /** @return array<int, array<string, mixed>> */
    public function forOrganization(int|string $organizationId): array
    {
        return $this->queryBuilder->table('property_owner_lifecycle_history')
            ->where('organization_id', '=', $organizationId)->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->get();
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    private function append(array $attributes): array
    {
        $id = $this->queryBuilder->table('property_owner_lifecycle_history')->insert($attributes);
        $record = $this->queryBuilder->table('property_owner_lifecycle_history')->where('id', '=', $id)
            ->where('organization_id', '=', $attributes['organization_id'])->first();
        if ($record === null) {
            throw new RuntimeException('Created lifecycle history event could not be retrieved.');
        }
        return $record;
    }
}
