<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\ServiceProvider;
use App\Routing\Router;

/**
 * Loads application route definitions after infrastructure services register.
 */
final class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $routes = require dirname(__DIR__, 2) . '/routes/api.php';
        $routes($this->container->make(Router::class), $this->container, $this->config);
    }
}
