<?php

declare(strict_types=1);

use App\Core\Container;
use App\Http\Request;
use App\Modules\Authentication\Controllers\AuthenticationController;
use App\Modules\Authentication\Middleware\AuthenticationMiddleware;
use App\Modules\Franchise\Controllers\FranchiseController;
use App\Modules\Organization\Controllers\OrganizationController;
use App\Modules\PartnerAgency\Controllers\PartnerAgencyController;
use App\Modules\Position\Controllers\PositionController;
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

    $router->get('/api/v1/health', function () use ($config): Response {
        return Response::success(
            'Application is running.',
            [
                'status' => 'ok',
                'version' => $config['app']['version'],
            ]
        );
    });

    $router->get('/organizations', $protect(function (Request $request) use ($container): Response {
        return $container->make(OrganizationController::class)->index($request);
    }));

    $router->get('/organizations/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(OrganizationController::class)->show($request, $id);
    }));

    $router->post('/organizations', $protect(function (Request $request) use ($container): Response {
        return $container->make(OrganizationController::class)->store($request);
    }));

    $router->put('/organizations/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(OrganizationController::class)->update($request, $id);
    }));

    $router->delete('/organizations/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(OrganizationController::class)->destroy($request, $id);
    }));

    $router->get('/franchises', $protect(function (Request $request) use ($container): Response {
        return $container->make(FranchiseController::class)->index($request);
    }));

    $router->get('/franchises/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(FranchiseController::class)->show($request, $id);
    }));

    $router->post('/franchises', $protect(function (Request $request) use ($container): Response {
        return $container->make(FranchiseController::class)->store($request);
    }));

    $router->put('/franchises/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(FranchiseController::class)->update($request, $id);
    }));

    $router->delete('/franchises/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(FranchiseController::class)->destroy($request, $id);
    }));

    $router->get('/partner-agencies', $protect(function (Request $request) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->index($request);
    }));

    $router->get('/partner-agencies/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->show($request, $id);
    }));

    $router->post('/partner-agencies', $protect(function (Request $request) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->store($request);
    }));

    $router->put('/partner-agencies/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->update($request, $id);
    }));

    $router->delete('/partner-agencies/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->destroy($request, $id);
    }));

    $router->post('/auth/login', function (Request $request) use ($container): Response {
        return $container->make(AuthenticationController::class)->login($request);
    });

    $router->post('/auth/logout', $protect(function (Request $request) use ($container): Response {
        return $container->make(AuthenticationController::class)->logout($request);
    }));

    $router->get('/auth/me', $protect(function (Request $request) use ($container): Response {
        return $container->make(AuthenticationController::class)->me($request);
    }));

    $router->get('/users', $protect(function (Request $request) use ($container): Response {
        return $container->make(UserController::class)->index($request);
    }));

    $router->get('/users/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(UserController::class)->show($request, $id);
    }));

    $router->post('/users', $protect(function (Request $request) use ($container): Response {
        return $container->make(UserController::class)->store($request);
    }));

    $router->put('/users/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(UserController::class)->update($request, $id);
    }));

    $router->delete('/users/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(UserController::class)->destroy($request, $id);
    }));

    $router->get('/positions', $protect(function (Request $request) use ($container): Response {
        return $container->make(PositionController::class)->index($request);
    }));

    $router->get('/positions/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(PositionController::class)->show($request, $id);
    }));

    $router->post('/positions', $protect(function (Request $request) use ($container): Response {
        return $container->make(PositionController::class)->store($request);
    }));

    $router->put('/positions/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(PositionController::class)->update($request, $id);
    }));

    $router->delete('/positions/{id}', $protect(function (Request $request, string $id) use ($container): Response {
        return $container->make(PositionController::class)->destroy($request, $id);
    }));
};
