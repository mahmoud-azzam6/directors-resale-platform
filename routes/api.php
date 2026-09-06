<?php

declare(strict_types=1);

use App\Core\Container;
use App\Http\Request;
use App\Modules\Authentication\Controllers\AuthenticationController;
use App\Modules\Authentication\Controllers\AuthContextController;
use App\Modules\Authentication\Middleware\AuthenticationMiddleware;
use App\Modules\Franchise\Controllers\FranchiseController;
use App\Modules\Organization\Controllers\OrganizationController;
use App\Modules\PartnerAgency\Controllers\PartnerAgencyController;
use App\Modules\Position\Controllers\PositionController;
use App\Modules\Permission\Controllers\PermissionController;
use App\Modules\Permission\Controllers\PositionPermissionController;
use App\Modules\Authorization\Middleware\AuthorizationMiddleware;
use App\Modules\Authorization\Services\AuthorizationService;
use App\Modules\Authorization\Services\OrganizationScopeService;
use App\Modules\Property\Controllers\OrganizationPropertyController;
use App\Modules\Owner\Controllers\OwnerController;
use App\Modules\Ownership\Controllers\OwnershipController;
use App\Modules\GlobalPropertyIdentity\Controllers\GlobalPhysicalIdentityController;
use App\Modules\User\Controllers\UserController;
use App\Modules\NetworkAdministration\Controllers\FranchiseOnboardingController;
use App\Responses\Response;
use App\Routing\Router;

/**
 * Register application API routes.
 *
 * @param array<string, mixed> $config
 */
return static function (Router $router, Container $container, array $config): void {
    $authenticationMiddleware = $container->make(AuthenticationMiddleware::class);
    $authorizationMiddleware = $container->make(AuthorizationMiddleware::class);
    $authorizationService = $container->make(AuthorizationService::class);
    $protect = static function (callable $handler) use ($authenticationMiddleware): callable {
        return static function (Request $request, ...$parameters) use ($authenticationMiddleware, $handler): Response {
            return $authenticationMiddleware->handle(
                $request,
                static fn (Request $authenticatedRequest): Response => $handler(
                    $authenticatedRequest,
                    ...$parameters
                )
            );
        };
    };
    $authorize = static function (
        callable $handler,
        string $permission,
        ?callable $targetResolver = null,
        string $scopeMode = OrganizationScopeService::HIERARCHY
    ) use ($protect, $authorizationMiddleware): callable {
        return $protect(static function (Request $request, ...$parameters) use ($authorizationMiddleware, $handler, $permission, $targetResolver, $scopeMode): Response {
            $resolver = $targetResolver === null ? null : static fn (Request $authorizedRequest): ?int => $targetResolver($authorizedRequest, ...$parameters);
            return $authorizationMiddleware->handle(
                $request,
                $permission,
                static function (Request $authorizedRequest) use ($handler, $parameters): Response {
                    $validation = $authorizedRequest->attribute('auth.target.validation_error');
                    if (is_array($validation)) {
                        return Response::json(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'The requested Organization target is invalid.', 'fields' => $validation]], 422);
                    }
                    if ($authorizedRequest->attribute('auth.target.missing', false) === true) {
                        return Response::error('not_found', 'Resource not found.', 404);
                    }
                    $candidate = $authorizedRequest->attribute('auth.target.organization_candidate');
                    if ($candidate !== null) {
                        $authorizedRequest->setAttribute('auth.target.organization_id', (int) $candidate);
                    }
                    return $handler($authorizedRequest, ...$parameters);
                },
                $resolver,
                $scopeMode
            );
        });
    };
    $target = static function (string $resource) use ($authorizationService): callable {
        return static function (Request $request, string $id) use ($authorizationService, $resource): ?int {
            $organizationId = $authorizationService->targetOrganization($resource, $id);
            if ($organizationId === null) { $request->setAttribute('auth.target.missing', true); }
            else { $request->setAttribute('auth.target.organization_candidate', $organizationId); }
            return $organizationId;
        };
    };
    $requestedTarget = static function (Request $request, bool $query = false): ?int {
        $value = $query ? $request->query('organization_id') : $request->input('organization_id');
        if ($value === null) { return null; }
        if ((! is_int($value) && (! is_string($value) || ! ctype_digit($value))) || (int) $value <= 0) {
            $request->setAttribute('auth.target.validation_error', ['organization_id' => 'Organization must be a valid identifier.']);
            return null;
        }
        $request->setAttribute('auth.target.organization_candidate', (int) $value);
        return (int) $value;
    };
    $createOrganizationTarget = static function (Request $request) use ($requestedTarget): ?int {
        $target = $requestedTarget($request);
        if ($target !== null || $request->attribute('auth.target.validation_error') !== null) { return $target; }
        $user = (array) $request->attribute('auth.user', []);
        $target = isset($user['organization_id']) ? (int) $user['organization_id'] : null;
        if ($target !== null) { $request->setAttribute('auth.target.organization_candidate', $target); }
        return $target;
    };
    $listOrganizationTarget = static fn (Request $request): ?int => $requestedTarget($request, true);
    $createTarget = static fn (Request $request): ?int => is_numeric($request->input('organization_id'))
        ? (int) $request->input('organization_id') : null;
    $franchiseParentTarget = static fn (Request $request): ?int => is_numeric($request->input('parent_organization_id'))
        ? (int) $request->input('parent_organization_id') : null;

    $router->get('/api/v1/health', function () use ($config): Response {
        return Response::success(
            'Application is running.',
            [
                'status' => 'ok',
                'version' => $config['app']['version'],
            ]
        );
    });

    $router->get('/organizations', $authorize(function (Request $request) use ($container): Response {
        return $container->make(OrganizationController::class)->index($request);
    }, 'organizations.view'));

    $router->get('/organizations/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(OrganizationController::class)->show($request, $id);
    }, 'organizations.view', $target('organizations')));

    $router->post('/organizations', $authorize(function (Request $request) use ($container): Response {
        return $container->make(OrganizationController::class)->store($request);
    }, 'organizations.create', $createTarget));

    $router->put('/organizations/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(OrganizationController::class)->update($request, $id);
    }, 'organizations.update', $target('organizations')));

    $router->delete('/organizations/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(OrganizationController::class)->destroy($request, $id);
    }, 'organizations.archive', $target('organizations')));

    $router->get('/franchises', $authorize(function (Request $request) use ($container): Response {
        return $container->make(FranchiseController::class)->index($request);
    }, 'franchises.view'));

    $router->get('/franchises/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(FranchiseController::class)->show($request, $id);
    }, 'franchises.view', $target('franchises')));

    $router->post('/franchises', $authorize(function (Request $request) use ($container): Response {
        return $container->make(FranchiseController::class)->store($request);
    }, 'franchises.create', $franchiseParentTarget));

    $router->post('/network/franchises/onboard', $authorize(function (Request $request) use ($container): Response {
        return $container->make(FranchiseOnboardingController::class)->store($request);
    }, 'franchises.create', static function (Request $request): ?int {
        $franchise = $request->input('franchise');
        return is_array($franchise) && is_numeric($franchise['parent_organization_id'] ?? null)
            ? (int) $franchise['parent_organization_id'] : null;
    }));

    $router->put('/franchises/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(FranchiseController::class)->update($request, $id);
    }, 'franchises.update', $target('franchises')));

    $router->delete('/franchises/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(FranchiseController::class)->destroy($request, $id);
    }, 'franchises.archive', $target('franchises')));

    $router->get('/partner-agencies', $authorize(function (Request $request) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->index($request);
    }, 'partner_agencies.view'));

    $router->get('/partner-agencies/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->show($request, $id);
    }, 'partner_agencies.view', $target('partner_agencies')));

    $router->post('/partner-agencies', $authorize(function (Request $request) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->store($request);
    }, 'partner_agencies.create', static function (Request $request): ?int {
        return is_numeric($request->input('parent_organization_id')) ? (int) $request->input('parent_organization_id') : null;
    }));

    $router->put('/partner-agencies/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->update($request, $id);
    }, 'partner_agencies.update', $target('partner_agencies')));

    $router->delete('/partner-agencies/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->destroy($request, $id);
    }, 'partner_agencies.archive', $target('partner_agencies')));

    $router->post('/auth/login', function (Request $request) use ($container): Response {
        return $container->make(AuthenticationController::class)->login($request);
    });

    $router->post('/auth/logout', $protect(function (Request $request) use ($container): Response {
        return $container->make(AuthenticationController::class)->logout($request);
    }));

    $router->get('/auth/me', $protect(function (Request $request) use ($container): Response {
        return $container->make(AuthenticationController::class)->me($request);
    }));

    $router->get('/auth/context', $protect(function (Request $request) use ($container): Response {
        return $container->make(AuthContextController::class)->show($request);
    }));

    $router->get('/users', $authorize(function (Request $request) use ($container): Response {
        return $container->make(UserController::class)->index($request);
    }, 'users.view'));

    $router->get('/user-administration/organizations', $authorize(function (Request $request) use ($container): Response {
        return $container->make(OrganizationController::class)->index($request);
    }, 'users.view'));

    $router->get('/user-administration/users', $authorize(function (Request $request) use ($container): Response {
        return $container->make(UserController::class)->administrativeIndex($request);
    }, 'users.view'));

    $router->get('/users/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(UserController::class)->show($request, $id);
    }, 'users.view', $target('users')));

    $router->post('/users', $authorize(function (Request $request) use ($container): Response {
        return $container->make(UserController::class)->store($request);
    }, 'users.create', $createTarget));

    $router->put('/users/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(UserController::class)->update($request, $id);
    }, 'users.update', $target('users')));

    $router->delete('/users/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(UserController::class)->destroy($request, $id);
    }, 'users.archive', $target('users')));

    $router->get('/positions', $authorize(function (Request $request) use ($container): Response {
        return $container->make(PositionController::class)->index($request);
    }, 'positions.view'));

    $router->get('/position-administration/organizations', $authorize(function (Request $request) use ($container): Response {
        return $container->make(OrganizationController::class)->index($request);
    }, 'positions.view'));

    $router->get('/positions/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(PositionController::class)->show($request, $id);
    }, 'positions.view', $target('positions')));

    $router->post('/positions', $authorize(function (Request $request) use ($container): Response {
        return $container->make(PositionController::class)->store($request);
    }, 'positions.create', $createTarget));

    $router->put('/positions/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(PositionController::class)->update($request, $id);
    }, 'positions.update', $target('positions')));

    $router->delete('/positions/{id}', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(PositionController::class)->destroy($request, $id);
    }, 'positions.archive', $target('positions')));

    $router->get('/permissions', $authorize(function (Request $request) use ($container): Response {
        return $container->make(PermissionController::class)->index($request);
    }, 'permissions.view'));

    $router->get('/positions/{id}/permissions', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(PositionPermissionController::class)->index($request, $id);
    }, 'permissions.view', $target('positions')));

    $router->put('/positions/{id}/permissions', $authorize(function (Request $request, string $id) use ($container): Response {
        return $container->make(PositionPermissionController::class)->update($request, $id);
    }, 'permissions.assign', $target('positions')));

    $router->get('/organization-properties', $authorize(fn (Request $request): Response => $container->make(OrganizationPropertyController::class)->index($request), 'properties.view', $listOrganizationTarget));
    $router->post('/organization-properties', $authorize(fn (Request $request): Response => $container->make(OrganizationPropertyController::class)->store($request), 'properties.manage', $createOrganizationTarget));
    $router->get('/organization-properties/{id}', $authorize(fn (Request $request, string $id): Response => $container->make(OrganizationPropertyController::class)->show($request, $id), 'properties.view', $target('organization_properties')));
    $router->put('/organization-properties/{id}', $authorize(fn (Request $request, string $id): Response => $container->make(OrganizationPropertyController::class)->update($request, $id), 'properties.manage', $target('organization_properties')));
    $router->delete('/organization-properties/{id}', $authorize(fn (Request $request, string $id): Response => $container->make(OrganizationPropertyController::class)->archive($request, $id), 'properties.manage', $target('organization_properties')));
    $router->post('/organization-properties/{id}/reactivate', $authorize(fn (Request $request, string $id): Response => $container->make(OrganizationPropertyController::class)->reactivate($request, $id), 'properties.manage', $target('organization_properties')));

    $router->get('/owners', $authorize(fn (Request $request): Response => $container->make(OwnerController::class)->index($request), 'owners.view', $listOrganizationTarget, OrganizationScopeService::PRIVATE_ORGANIZATION));
    $router->post('/owners', $authorize(fn (Request $request): Response => $container->make(OwnerController::class)->store($request), 'owners.manage', $createOrganizationTarget, OrganizationScopeService::PRIVATE_ORGANIZATION));
    $router->get('/owners/{id}', $authorize(fn (Request $request, string $id): Response => $container->make(OwnerController::class)->show($request, $id), 'owners.view', $target('owners'), OrganizationScopeService::PRIVATE_ORGANIZATION));
    $router->put('/owners/{id}', $authorize(fn (Request $request, string $id): Response => $container->make(OwnerController::class)->update($request, $id), 'owners.manage', $target('owners'), OrganizationScopeService::PRIVATE_ORGANIZATION));
    $router->delete('/owners/{id}', $authorize(fn (Request $request, string $id): Response => $container->make(OwnerController::class)->deactivate($request, $id), 'owners.manage', $target('owners'), OrganizationScopeService::PRIVATE_ORGANIZATION));
    $router->post('/owners/{id}/reactivate', $authorize(fn (Request $request, string $id): Response => $container->make(OwnerController::class)->reactivate($request, $id), 'owners.manage', $target('owners'), OrganizationScopeService::PRIVATE_ORGANIZATION));

    $propertyTarget = $target('organization_properties');
    $ownershipTarget = $target('ownerships');
    $private = OrganizationScopeService::PRIVATE_ORGANIZATION;
    $systemOnly = OrganizationScopeService::SYSTEM_ONLY;
    $router->post('/organization-properties/{propertyId}/ownerships', $authorize(fn (Request $request, string $propertyId): Response => $container->make(OwnershipController::class)->store($request, $propertyId), 'ownerships.manage', $propertyTarget, $private));
    $router->get('/organization-properties/{propertyId}/ownerships/current', $authorize(fn (Request $request, string $propertyId): Response => $container->make(OwnershipController::class)->currentForProperty($request, $propertyId), 'ownerships.view', $propertyTarget, $private));
    $router->get('/organization-properties/{propertyId}/ownerships', $authorize(fn (Request $request, string $propertyId): Response => $container->make(OwnershipController::class)->historyForProperty($request, $propertyId), 'ownerships.view', $propertyTarget, $private));
    $router->get('/ownerships/{id}', $authorize(fn (Request $request, string $id): Response => $container->make(OwnershipController::class)->show($request, $id), 'ownerships.view', $ownershipTarget, $private));
    $router->post('/ownerships/{id}/close', $authorize(fn (Request $request, string $id): Response => $container->make(OwnershipController::class)->close($request, $id), 'ownerships.manage', $ownershipTarget, $private));
    $router->get('/ownerships/{id}/parties', $authorize(fn (Request $request, string $id): Response => $container->make(OwnershipController::class)->parties($request, $id), 'ownerships.view', $ownershipTarget, $private));
    $router->post('/ownerships/{id}/parties', $authorize(fn (Request $request, string $id): Response => $container->make(OwnershipController::class)->addParty($request, $id), 'ownerships.manage', $ownershipTarget, $private));
    $router->put('/ownerships/{id}/parties/{partyId}', $authorize(fn (Request $request, string $id, string $partyId): Response => $container->make(OwnershipController::class)->updatePartyShare($request, $id, $partyId), 'ownerships.manage', $ownershipTarget, $private));
    $router->delete('/ownerships/{id}/parties/{partyId}', $authorize(fn (Request $request, string $id, string $partyId): Response => $container->make(OwnershipController::class)->removeParty($request, $id, $partyId), 'ownerships.manage', $ownershipTarget, $private));
    $router->get('/ownerships/{id}/acting-owner', $authorize(fn (Request $request, string $id): Response => $container->make(OwnershipController::class)->currentActingOwner($request, $id), 'ownerships.view', $ownershipTarget, $private));
    $router->post('/ownerships/{id}/acting-owner', $authorize(fn (Request $request, string $id): Response => $container->make(OwnershipController::class)->designateActingOwner($request, $id), 'ownerships.manage', $ownershipTarget, $private));
    $router->put('/ownerships/{id}/acting-owner', $authorize(fn (Request $request, string $id): Response => $container->make(OwnershipController::class)->changeActingOwner($request, $id), 'ownerships.manage', $ownershipTarget, $private));
    $router->delete('/ownerships/{id}/acting-owner', $authorize(fn (Request $request, string $id): Response => $container->make(OwnershipController::class)->clearActingOwner($request, $id), 'ownerships.manage', $ownershipTarget, $private));
    $router->get('/ownerships/{id}/acting-owner/history', $authorize(fn (Request $request, string $id): Response => $container->make(OwnershipController::class)->actingOwnerHistory($request, $id), 'ownerships.view', $ownershipTarget, $private));

    $router->get('/global-physical-identities', $authorize(fn (Request $request): Response => $container->make(GlobalPhysicalIdentityController::class)->index($request), 'global_physical_identities.view', null, $systemOnly));
    $router->post('/global-physical-identities', $authorize(fn (Request $request): Response => $container->make(GlobalPhysicalIdentityController::class)->store($request), 'global_physical_identities.manage', null, $systemOnly));
    $router->get('/global-physical-identities/{id}', $authorize(fn (Request $request, string $id): Response => $container->make(GlobalPhysicalIdentityController::class)->show($request, $id), 'global_physical_identities.view', null, $systemOnly));
    $router->get('/global-physical-identities/{id}/representations', $authorize(fn (Request $request, string $id): Response => $container->make(GlobalPhysicalIdentityController::class)->representations($request, $id), 'global_physical_identities.view', null, $systemOnly));
    $router->get('/organization-properties/{propertyId}/global-identity-link', $authorize(fn (Request $request, string $propertyId): Response => $container->make(GlobalPhysicalIdentityController::class)->activeLink($request, $propertyId), 'global_physical_identities.view', $propertyTarget, $systemOnly));
    $router->post('/organization-properties/{propertyId}/global-identity-link', $authorize(fn (Request $request, string $propertyId): Response => $container->make(GlobalPhysicalIdentityController::class)->link($request, $propertyId), 'global_physical_identities.manage', $propertyTarget, $systemOnly));
    $router->put('/organization-properties/{propertyId}/global-identity-link', $authorize(fn (Request $request, string $propertyId): Response => $container->make(GlobalPhysicalIdentityController::class)->relink($request, $propertyId), 'global_physical_identities.manage', $propertyTarget, $systemOnly));
    $router->delete('/organization-properties/{propertyId}/global-identity-link', $authorize(fn (Request $request, string $propertyId): Response => $container->make(GlobalPhysicalIdentityController::class)->unlink($request, $propertyId), 'global_physical_identities.manage', $propertyTarget, $systemOnly));
    $router->get('/organization-properties/{propertyId}/global-identity-link/history', $authorize(fn (Request $request, string $propertyId): Response => $container->make(GlobalPhysicalIdentityController::class)->linkHistory($request, $propertyId), 'global_physical_identities.view', $propertyTarget, $systemOnly));
};
