<?php

declare(strict_types=1);

namespace App\Routing;

use App\Http\Request;
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
    public function dispatch(string $method, string $uri, Request $request)
    {
        $method = strtoupper($method);
        $path = $this->normalizePath((string) parse_url($uri, PHP_URL_PATH));

        if (! isset($this->routes[$method][$path])) {
            foreach ($this->routes[$method] ?? [] as $route => $handler) {
                $parameters = $this->matchParameters($route, $path);

                if ($parameters !== null) {
                    return $handler($request, ...$parameters);
                }
            }

            return Response::error('not_found', 'Route not found.', 404);
        }

        return $this->routes[$method][$path]($request);
    }

    /**
     * @return array<int, string>|null
     */
    private function matchParameters(string $route, string $path): ?array
    {
        $routeSegments = explode('/', trim($route, '/'));
        $pathSegments = explode('/', trim($path, '/'));

        if (count($routeSegments) !== count($pathSegments)) {
            return null;
        }

        $parameters = [];

        foreach ($routeSegments as $index => $routeSegment) {
            $pathSegment = $pathSegments[$index];

            if (str_starts_with($routeSegment, '{') && str_ends_with($routeSegment, '}')) {
                $parameters[] = rawurldecode($pathSegment);

                continue;
            }

            if ($routeSegment !== $pathSegment) {
                return null;
            }
        }

        return $parameters;
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
