<?php

declare(strict_types=1);

namespace App\Modules\NetworkAdministration\Services;

use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Franchise\Services\FranchiseService;
use App\Modules\Permission\Services\PositionPermissionService;
use App\Modules\Position\Services\PositionService;
use App\Modules\User\Services\UserService;

/**
 * Atomically provisions a Franchise and its initial credential-less administrator.
 */
final class FranchiseOnboardingService
{
    public function __construct(
        private FranchiseService $franchiseService,
        private PositionService $positionService,
        private PositionPermissionService $permissionService,
        private UserService $userService,
        private DatabaseConnectionInterface $database
    ) {
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function onboard(array $data): array
    {
        foreach (['franchise', 'position', 'administrator', 'permissions'] as $section) {
            if (! array_key_exists($section, $data) || ! is_array($data[$section])) {
                throw new ValidationException([$section => ucfirst($section) . ' data is required.']);
            }
        }

        if ($data['permissions'] === []) {
            throw new ValidationException(['permissions' => 'At least one administrator Permission is required.']);
        }

        return $this->database->transaction(function () use ($data): array {
            $franchise = $this->franchiseService->create($data['franchise']);
            $position = $this->positionService->create(array_merge($data['position'], [
                'organization_id' => $franchise['id'],
                'status' => 'active',
            ]));
            $permissions = $this->permissionService->replace($position['id'], $data['permissions']);
            $administrator = $this->userService->create(array_merge($data['administrator'], [
                'organization_id' => $franchise['id'],
                'position_id' => $position['id'],
                'status' => 'inactive',
            ]));

            return [
                'franchise' => $franchise,
                'position' => $position,
                'permissions' => $permissions,
                'administrator' => $administrator,
                'activation_status' => 'credential_setup_required',
            ];
        });
    }
}
