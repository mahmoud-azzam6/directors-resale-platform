<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\DatabaseManager;
use App\Core\Database\BaseQueryBuilder;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\Database\QueryBuilderInterface;
use App\Core\ServiceProvider;
use App\Exceptions\ExceptionHandler;
use App\Http\Kernel;
use App\Http\Request;
use App\Responses\Response;
use App\Routing\Router;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Logger::class, function (): Logger {
            $logger = new Logger((string) $this->config['app']['name']);
            $logger->pushHandler(new StreamHandler(
                (string) $this->config['app']['log']['path'],
                (int) $this->config['app']['log']['level']
            ));

            return $logger;
        });

        $this->container->singleton(DatabaseManager::class, function (): DatabaseManager {
            return new DatabaseManager($this->config['database']);
        });

        $this->container->singleton(
            DatabaseConnectionInterface::class,
            fn (): DatabaseConnectionInterface => $this->container->make(DatabaseManager::class)
        );

        $this->container->bind(BaseQueryBuilder::class);
        $this->container->bind(QueryBuilderInterface::class, BaseQueryBuilder::class);

        $this->container->singleton(Kernel::class);
        $this->container->singleton(
            Request::class,
            fn (): Request => $this->container->make(Kernel::class)->createRequest()
        );

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
