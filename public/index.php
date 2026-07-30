<?php

declare(strict_types=1);

use App\Responses\Response;
use App\Routing\Router;

$app = require dirname(__DIR__) . '/bootstrap/app.php';

$container = $app->container();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

$basePath = $app->config()['app']['base_path'] ?? '';

if ($basePath !== '' && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}

$uri = $uri === '' ? '/' : $uri;

$response = $container->make(Router::class)->dispatch($method, $uri);

if ($response instanceof Response) {
    $response->send();

    return;
}

Response::json(['success' => true, 'data' => $response])->send();
