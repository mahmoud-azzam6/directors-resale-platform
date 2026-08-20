<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Controllers;

use App\Http\Request;
use App\Modules\Authorization\Services\AuthorizationService;
use App\Modules\Organization\Repositories\OrganizationRepository;
use App\Responses\Response;

/**
 * Returns the safe identity and authorization context needed by the Admin UI.
 */
final class AuthContextController
{
    public function __construct(
        private OrganizationRepository $organizationRepository,
        private AuthorizationService $authorizationService
    ) {
    }

    public function show(Request $request): Response
    {
        $user = $request->attribute('auth.user');
        $organization = is_array($user)
            ? $this->organizationRepository->find((int) $user['organization_id'])
            : null;
        $position = is_array($user) ? $this->authorizationService->effectivePosition($user) : null;
        $permissions = is_array($user) ? $this->authorizationService->effectivePermissionCodes($user) : [];

        return Response::success('Authentication context retrieved.', [
            'user' => $user,
            'organization' => $organization,
            'position' => $position,
            'permissions' => $permissions,
        ]);
    }
}
