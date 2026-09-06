<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Services;

use App\Modules\Organization\Repositories\OrganizationRepository;
use InvalidArgumentException;

final class OrganizationScopeService
{
    public const HIERARCHY = 'hierarchy';
    public const PRIVATE_ORGANIZATION = 'private_organization';
    public const SYSTEM_ONLY = 'system_only';

    public function __construct(private OrganizationRepository $repository) {}

    /** @return array<int, int> */
    public function organizationIds(int $actorOrganizationId, string $mode = self::HIERARCHY): array
    {
        if (! in_array($mode, [self::HIERARCHY, self::PRIVATE_ORGANIZATION, self::SYSTEM_ONLY], true)) {
            throw new InvalidArgumentException('Unsupported authorization scope mode.');
        }

        $organizations = $this->repository->all();
        $actor = null;
        foreach ($organizations as $organization) {
            if ((int) $organization['id'] === $actorOrganizationId) { $actor = $organization; break; }
        }
        if ($actor === null) { return []; }
        if (($actor['organization_type'] ?? null) === 'system') {
            return array_map(static fn (array $row): int => (int) $row['id'], $organizations);
        }
        if ($mode === self::SYSTEM_ONLY) { return []; }
        if ($mode === self::PRIVATE_ORGANIZATION) { return [$actorOrganizationId]; }
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

    public function allows(
        int $actorOrganizationId,
        int $targetOrganizationId,
        string $mode = self::HIERARCHY
    ): bool {
        return in_array($targetOrganizationId, $this->organizationIds($actorOrganizationId, $mode), true);
    }
}
