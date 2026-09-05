<?php

declare(strict_types=1);

namespace App\Modules\Owner\Repositories;

use App\Core\Database\QueryBuilderInterface;
use RuntimeException;

final class OwnerRepository
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    public function create(array $attributes): array
    {
        $id = $this->queryBuilder->table('owners')->insert($attributes);
        $record = $this->findInOrganization($id, (int) $attributes['organization_id']);

        if ($record === null) {
            throw new RuntimeException('Created Owner could not be retrieved.');
        }

        return $record;
    }

    /** @return array<string, mixed>|null */
    public function findInOrganization(int|string $id, int|string $organizationId): ?array
    {
        return $this->queryBuilder->table('owners')->where('id', '=', $id)
            ->where('organization_id', '=', $organizationId)->first();
    }

    /** @return array<string, mixed>|null */
    public function findForUpdate(int|string $id, int|string $organizationId): ?array
    {
        return $this->queryBuilder->table('owners')->where('id', '=', $id)
            ->where('organization_id', '=', $organizationId)->forUpdate()->first();
    }

    /** @param array<int, int> $organizationIds @return array<int, array<string, mixed>> */
    public function allInScope(array $organizationIds): array
    {
        if ($organizationIds === []) {
            return [];
        }

        return $this->queryBuilder->table('owners')->whereIn('organization_id', $organizationIds)
            ->orderBy('display_name')->get();
    }

    /** @return array<int, array<string, mixed>> */
    public function searchByDisplayName(int|string $organizationId, string $displayName): array
    {
        return $this->queryBuilder->table('owners')->where('organization_id', '=', $organizationId)
            ->where('display_name', 'LIKE', '%' . $displayName . '%')->orderBy('display_name')->get();
    }

    /** @return array<int, array<string, mixed>> */
    public function findByMobileNormalized(int|string $organizationId, string $mobile): array
    {
        return $this->queryBuilder->table('owners')->where('organization_id', '=', $organizationId)
            ->where('mobile_normalized', '=', $mobile)->get();
    }

    /** @return array<int, array<string, mixed>> */
    public function findByEmailNormalized(int|string $organizationId, string $email): array
    {
        return $this->queryBuilder->table('owners')->where('organization_id', '=', $organizationId)
            ->where('email_normalized', '=', $email)->get();
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed>|null */
    public function updateStoredFields(int|string $id, int|string $organizationId, array $attributes): ?array
    {
        $allowed = array_intersect_key($attributes, array_flip([
            'party_type', 'display_name', 'mobile', 'mobile_normalized', 'email', 'email_normalized',
            'preferred_contact_method', 'contact_person_name', 'contact_person_mobile',
            'contact_person_email', 'updated_by_user_id',
        ]));

        if ($allowed !== []) {
            $this->queryBuilder->table('owners')->where('id', '=', $id)
                ->where('organization_id', '=', $organizationId)->update($allowed);
        }

        return $this->findInOrganization($id, $organizationId);
    }

    /** @return array<string, mixed>|null */
    public function deactivate(int|string $id, int|string $organizationId, string $at, int|string $userId): ?array
    {
        $this->queryBuilder->table('owners')->where('id', '=', $id)
            ->where('organization_id', '=', $organizationId)->update([
            'status' => 'inactive', 'deactivated_at' => $at,
            'deactivated_by_user_id' => $userId, 'updated_by_user_id' => $userId,
        ]);

        return $this->findInOrganization($id, $organizationId);
    }

    /** @return array<string, mixed>|null */
    public function reactivate(int|string $id, int|string $organizationId, int|string $userId): ?array
    {
        $this->queryBuilder->table('owners')->where('id', '=', $id)
            ->where('organization_id', '=', $organizationId)->update([
            'status' => 'active', 'deactivated_at' => null,
            'deactivated_by_user_id' => null, 'updated_by_user_id' => $userId,
        ]);

        return $this->findInOrganization($id, $organizationId);
    }
}
