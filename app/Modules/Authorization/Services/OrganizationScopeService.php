<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Services;

use App\Modules\Organization\Repositories\OrganizationRepository;

final class OrganizationScopeService
{
    public function __construct(private OrganizationRepository $repository) {}

    /** @return array<int, int> */
    public function organizationIds(int $actorOrganizationId): array
    {
        $organizations = $this->repository->all();
        $actor = null;
        foreach ($organizations as $organization) {
            if ((int) $organization['id'] === $actorOrganizationId) { $actor = $organization; break; }
        }
        if ($actor === null) { return []; }
        if (($actor['organization_type'] ?? null) === 'system') {
            return array_map(static fn (array $row): int => (int) $row['id'], $organizations);
        }
        $ids = [$actorOrganizationId];
        if (($actor['organization_type'] ?? null) === 'franchise') {
            foreach ($organizations as $organization) {
                if ((int) ($organization['parent_organization_id'] ?? 0) === $actorOrganizationId) {
                    $ids[] = (int) $organization['id'];
                }
            }
        }
        return array_values(array_unique($ids));
    }

    public function allows(int $actorOrganizationId, int $targetOrganizationId): bool
    { return in_array($targetOrganizationId, $this->organizationIds($actorOrganizationId), true); }
}