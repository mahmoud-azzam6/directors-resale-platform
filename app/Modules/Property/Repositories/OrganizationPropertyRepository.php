<?php

declare(strict_types=1);

namespace App\Modules\Property\Repositories;

use App\Core\Database\QueryBuilderInterface;
use RuntimeException;

final class OrganizationPropertyRepository
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    public function create(array $attributes): array
    {
        $id = $this->queryBuilder->table('organization_properties')->insert($attributes);
        $record = $this->findInOrganization($id, (int) $attributes['organization_id']);

        if ($record === null) {
            throw new RuntimeException('Created Organization Property could not be retrieved.');
        }

        return $record;
    }

    /** @return array<string, mixed>|null */
    public function findInOrganization(int|string $id, int|string $organizationId): ?array
    {
        return $this->queryBuilder->table('organization_properties')
            ->where('id', '=', $id)
            ->where('organization_id', '=', $organizationId)
            ->first();
    }

    /** @return array<string, mixed>|null */
    public function findForUpdate(int|string $id, int|string $organizationId): ?array
    {
        return $this->queryBuilder->table('organization_properties')
            ->where('id', '=', $id)
            ->where('organization_id', '=', $organizationId)
            ->forUpdate()
            ->first();
    }

    /** @param array<int, int> $organizationIds @return array<int, array<string, mixed>> */
    public function allInScope(array $organizationIds): array
    {
        if ($organizationIds === []) {
            return [];
        }

        return $this->queryBuilder->table('organization_properties')
            ->whereIn('organization_id', $organizationIds)
            ->orderBy('id')
            ->get();
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed>|null */
    public function updateStoredFields(int|string $id, int|string $organizationId, array $attributes): ?array
    {
        $allowed = array_intersect_key($attributes, array_flip([
            'property_label', 'updated_by_user_id',
        ]));

        if ($allowed !== []) {
            $this->queryBuilder->table('organization_properties')
                ->where('id', '=', $id)
                ->where('organization_id', '=', $organizationId)
                ->update($allowed);
        }

        return $this->findInOrganization($id, $organizationId);
    }

    /** @return array<string, mixed>|null */
    public function archive(int|string $id, int|string $organizationId, string $archivedAt, int|string $userId): ?array
    {
        $this->queryBuilder->table('organization_properties')
            ->where('id', '=', $id)
            ->where('organization_id', '=', $organizationId)
            ->update([
            'status' => 'archived',
            'archived_at' => $archivedAt,
            'archived_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return $this->findInOrganization($id, $organizationId);
    }

    /** @return array<string, mixed>|null */
    public function reactivate(int|string $id, int|string $organizationId, int|string $userId): ?array
    {
        $this->queryBuilder->table('organization_properties')
            ->where('id', '=', $id)
            ->where('organization_id', '=', $organizationId)
            ->update([
            'status' => 'active',
            'archived_at' => null,
            'archived_by_user_id' => null,
            'updated_by_user_id' => $userId,
        ]);

        return $this->findInOrganization($id, $organizationId);
    }
}
