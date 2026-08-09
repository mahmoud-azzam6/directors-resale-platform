<?php

declare(strict_types=1);

use App\Http\Request;
use App\Responses\Response;
use App\Routing\Router;

$app = require dirname(__DIR__) . '/bootstrap/app.php';

$container = $app->container();

$request = $container->make(Request::class);
$uri = $request->uri();

$basePath = $app->config()['app']['base_path'] ?? '';

if ($basePath !== '' && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}

$uri = $uri === '' ? '/' : $uri;

$response = $container->make(Router::class)->dispatch($request->method(), $uri, $request);

if ($response instanceof Response) {
    $response->send();

    return;
}

Response::json(['success' => true, 'data' => $response])->send();
