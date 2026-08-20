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
use App\Modules\User\Controllers\UserController;
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
    $authorize = static function (callable $handler, string $permission, ?callable $targetResolver = null) use ($protect, $authorizationMiddleware): callable {
        return $protect(static function (Request $request, ...$parameters) use ($authorizationMiddleware, $handler, $permission, $targetResolver): Response {
            $resolver = $targetResolver === null ? null : static fn (Request $authorizedRequest): ?int => $targetResolver($authorizedRequest, ...$parameters);
            return $authorizationMiddleware->handle(
                $request,
                $permission,
                static fn (Request $authorizedRequest): Response => $handler($authorizedRequest, ...$parameters),
                $resolver
            );
        });
    };
    $target = static function (string $resource) use ($authorizationService): callable {
        return static function (Request $request, string $id) use ($authorizationService, $resource): ?int {
            return $authorizationService->targetOrganization($resource, $id);
        };
    };
    $createTarget = static fn (Request $request): ?int => is_numeric($request->input('organization_id'))
        ? (int) $request->input('organization_id') : null;

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
    }, 'franchises.create', $createTarget));

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
};
