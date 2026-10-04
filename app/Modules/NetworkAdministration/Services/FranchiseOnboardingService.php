<?php

declare(strict_types=1);

namespace App\Modules\NetworkAdministration\Services;

use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Franchise\Services\FranchiseService;
use App\Modules\Permission\Services\PositionPermissionService;
use App\Modules\Position\Services\PositionService;
use App\Modules\User\Services\UserService;
use App\Modules\Organization\Services\OrganizationBasicProfileService;

/**
 * Atomically provisions a Franchise and its initial active administrator.
 */
final class FranchiseOnboardingService
{
    public function __construct(
        private FranchiseService $franchiseService,
        private PositionService $positionService,
        private PositionPermissionService $permissionService,
        private UserService $userService,
        private OrganizationBasicProfileService $basicProfiles,
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
        $password = $data['administrator']['password'] ?? null;
        $confirmation = $data['administrator']['password_confirmation'] ?? null;
        if (! is_string($password) || $password === '') {
            throw new ValidationException(['password' => 'Password is required.']);
        }
        if (! is_string($confirmation) || $confirmation !== $password) {
            throw new ValidationException(['password_confirmation' => 'Password confirmation must match.']);
        }

        return $this->database->transaction(function () use ($data, $password): array {
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
            $administrator = $this->userService->activateWithInitialPassword($administrator['id'], $password);
            $basicProfile = null;
            if (array_key_exists('basic_profile', $data) && is_array($data['basic_profile'])) {
                $basicProfile = $this->basicProfiles->save((int) $franchise['id'], $data['basic_profile'], (int) $administrator['id']);
            }

            return [
                'franchise' => $franchise,
                'position' => $position,
                'permissions' => $permissions,
                'administrator' => $administrator,
                'basic_profile' => $basicProfile,
                'activation_status' => 'active',
            ];
        });
    }
}
