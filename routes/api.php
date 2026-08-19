<?php

declare(strict_types=1);

use App\Core\Container;
use App\Http\Request;
use App\Modules\Franchise\Controllers\FranchiseController;
use App\Modules\Organization\Controllers\OrganizationController;
use App\Modules\PartnerAgency\Controllers\PartnerAgencyController;
use App\Modules\User\Controllers\UserController;
use App\Responses\Response;
use App\Routing\Router;

/**
 * Register application API routes.
 *
 * @param array<string, mixed> $config
 */
return static function (Router $router, Container $container, array $config): void {
    $router->get('/api/v1/health', function () use ($config): Response {
        return Response::success(
            'Application is running.',
            [
                'status' => 'ok',
                'version' => $config['app']['version'],
            ]
        );
    });

    $router->get('/organizations', function (Request $request) use ($container): Response {
        return $container->make(OrganizationController::class)->index($request);
    });

    $router->get('/organizations/{id}', function (Request $request, string $id) use ($container): Response {
        return $container->make(OrganizationController::class)->show($request, $id);
    });

    $router->post('/organizations', function (Request $request) use ($container): Response {
        return $container->make(OrganizationController::class)->store($request);
    });

    $router->put('/organizations/{id}', function (Request $request, string $id) use ($container): Response {
        return $container->make(OrganizationController::class)->update($request, $id);
    });

    $router->delete('/organizations/{id}', function (Request $request, string $id) use ($container): Response {
        return $container->make(OrganizationController::class)->destroy($request, $id);
    });

    $router->get('/franchises', function (Request $request) use ($container): Response {
        return $container->make(FranchiseController::class)->index($request);
    });

    $router->get('/franchises/{id}', function (Request $request, string $id) use ($container): Response {
        return $container->make(FranchiseController::class)->show($request, $id);
    });

    $router->post('/franchises', function (Request $request) use ($container): Response {
        return $container->make(FranchiseController::class)->store($request);
    });

    $router->put('/franchises/{id}', function (Request $request, string $id) use ($container): Response {
        return $container->make(FranchiseController::class)->update($request, $id);
    });

    $router->delete('/franchises/{id}', function (Request $request, string $id) use ($container): Response {
        return $container->make(FranchiseController::class)->destroy($request, $id);
    });

    $router->get('/partner-agencies', function (Request $request) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->index($request);
    });

    $router->get('/partner-agencies/{id}', function (Request $request, string $id) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->show($request, $id);
    });

    $router->post('/partner-agencies', function (Request $request) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->store($request);
    });

    $router->put('/partner-agencies/{id}', function (Request $request, string $id) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->update($request, $id);
    });

    $router->delete('/partner-agencies/{id}', function (Request $request, string $id) use ($container): Response {
        return $container->make(PartnerAgencyController::class)->destroy($request, $id);
    });

    $router->get('/users', function (Request $request) use ($container): Response {
        return $container->make(UserController::class)->index($request);
    });

    $router->get('/users/{id}', function (Request $request, string $id) use ($container): Response {
        return $container->make(UserController::class)->show($request, $id);
    });

    $router->post('/users', function (Request $request) use ($container): Response {
        return $container->make(UserController::class)->store($request);
    });

    $router->put('/users/{id}', function (Request $request, string $id) use ($container): Response {
        return $container->make(UserController::class)->update($request, $id);
    });

    $router->delete('/users/{id}', function (Request $request, string $id) use ($container): Response {
        return $container->make(UserController::class)->destroy($request, $id);
    });
};
