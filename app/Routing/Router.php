<?php

declare(strict_types=1);

namespace App\Routing;

use App\Responses\Response;

final class Router
{
    /**
     * @var array<string, array<string, callable>>
     */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, callable $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[strtoupper($method)][$this->normalizePath($path)] = $handler;
    }

    /**
     * @return mixed
     */
    public function dispatch(string $method, string $uri)
    {
        $method = strtoupper($method);
        $path = $this->normalizePath((string) parse_url($uri, PHP_URL_PATH));

        if (! isset($this->routes[$method][$path])) {
            return Response::error('not_found', 'Route not found.', 404);
        }

        return $this->routes[$method][$path]();
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
