<?php

declare(strict_types=1);

namespace App\Modules\NetworkAdministration\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Authorization\Services\AuthorizationService;
use App\Modules\NetworkAdministration\Services\FranchiseOnboardingService;
use App\Responses\Response;

final class FranchiseOnboardingController
{
    public function __construct(
        private FranchiseOnboardingService $service,
        private AuthorizationService $authorizationService
    ) {
    }

    public function store(Request $request): Response
    {
        $user = $request->attribute('auth.user');
        $franchise = $request->input('franchise');
        $parentId = is_array($franchise) ? ($franchise['parent_organization_id'] ?? null) : null;

        if (! is_array($user)) {
            return Response::error('unauthenticated', 'Authentication is required.', 401);
        }

        foreach (['positions.create', 'permissions.assign', 'users.create'] as $permission) {
            $failure = $this->authorizationService->authorize(
                $user,
                $permission,
                is_numeric($parentId) ? (int) $parentId : null
            );
            if ($failure !== null) {
                return $failure;
            }
        }

        try {
            return Response::success(
                'Franchise onboarding completed. Administrator credential activation is required.',
                $this->service->onboard($request->all()),
                [],
                201
            );
        } catch (ValidationException $exception) {
            return Response::json([
                'success' => false,
                'error' => [
                    'code' => 'validation_error',
                    'message' => 'The Franchise onboarding data is invalid.',
                    'fields' => $exception->errors(),
                ],
            ], 422);
        }
    }
}
