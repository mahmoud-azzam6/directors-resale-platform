<?php

declare(strict_types=1);

use App\Core\Container;
use App\Http\Request;
use App\Modules\Organization\Controllers\OrganizationController;
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
};
