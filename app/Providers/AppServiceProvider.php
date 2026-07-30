<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\DatabaseManager;
use App\Core\ServiceProvider;
use App\Exceptions\ExceptionHandler;
use App\Responses\Response;
use App\Routing\Router;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(DatabaseManager::class, function (): DatabaseManager {
            return new DatabaseManager($this->config['database']);
        });

        $this->container->singleton(Router::class, function (): Router {
            $router = new Router();

            $router->get('/api/v1/health', function (): Response {
                return Response::success(
                    'Application is running.',
                    [
                        'status' => 'ok',
                        'version' => $this->config['app']['version'],
                    ]
                );
            });

            return $router;
        });

        $this->container->singleton(ExceptionHandler::class);
    }
}
