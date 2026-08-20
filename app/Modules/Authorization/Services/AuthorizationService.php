<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Services;

use App\Modules\Permission\Repositories\PermissionRepository;
use App\Modules\Permission\Repositories\PositionPermissionRepository;
use App\Modules\Organization\Repositories\OrganizationRepository;
use App\Modules\Franchise\Repositories\FranchiseRepository;
use App\Modules\PartnerAgency\Repositories\PartnerAgencyRepository;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\Position\Repositories\PositionRepository;
use App\Responses\Response;

final class AuthorizationService
{
    public function __construct(
        private PositionRepository $positionRepository,
        private PermissionRepository $permissionRepository,
        private PositionPermissionRepository $assignmentRepository,
        private OrganizationScopeService $scopeService,
        private OrganizationRepository $organizationRepository,
        private FranchiseRepository $franchiseRepository,
        private PartnerAgencyRepository $partnerAgencyRepository,
        private UserRepository $userRepository
    ) {}

    /** @return array<int, int> */
    public function scopeFor(array $user): array
    { return $this->scopeService->organizationIds((int) $user['organization_id']); }

    public function authorize(array $user, string $permissionCode, ?int $targetOrganizationId = null): ?Response
    {
        if (!$this->hasPermission($user, $permissionCode)) {
            return Response::error('forbidden', 'You do not have permission to perform this action.', 403);
        }
        if ($targetOrganizationId !== null
            && !$this->scopeService->allows((int) $user['organization_id'], $targetOrganizationId)) {
            return Response::error('forbidden', 'The requested Organization is outside your scope.', 403);
        }
        return null;
    }

    /** @return array<string, mixed>|null */
    public function effectivePosition(array $user): ?array
    {
        if (($user['status'] ?? null) !== 'active' || empty($user['position_id'])) { return null; }
        $position = $this->positionRepository->findActive((int) $user['position_id']);
        return $position !== null
            && (int) $position['organization_id'] === (int) $user['organization_id'] ? $position : null;
    }

    /** @return array<int, string> */
    public function effectivePermissionCodes(array $user): array
    {
        $position = $this->effectivePosition($user);
        if ($position === null) { return []; }
        $codes = [];
        foreach ($this->assignmentRepository->forPosition((int) $position['id']) as $assignment) {
            $permission = $this->permissionRepository->findActive((int) $assignment['permission_id']);
            if ($permission !== null) { $codes[] = (string) $permission['code']; }
        }
        return array_values(array_unique($codes));
    }

    public function targetOrganization(string $resource, int|string $id): ?int
    {
        if ($resource === 'organizations') {
            return $this->organizationRepository->find($id) === null ? null : (int) $id;
        }

        $record = match ($resource) {
            'franchises' => $this->franchiseRepository->findActive($id),
            'partner_agencies' => $this->partnerAgencyRepository->findActive($id),
            'users' => $this->userRepository->findActive($id),
            'positions' => $this->positionRepository->findActive($id),
            default => null,
        };
        return $record === null ? null : (int) $record['organization_id'];
    }

    private function hasPermission(array $user, string $code): bool
    {
        return in_array($code, $this->effectivePermissionCodes($user), true);
    }
}
