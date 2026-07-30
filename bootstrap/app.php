<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Container;
use App\Exceptions\ExceptionHandler;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$basePath = dirname(__DIR__);
$autoloadPath = $basePath . '/vendor/autoload.php';

if (! file_exists($autoloadPath)) {
    throw new RuntimeException('Composer dependencies are not installed. Run composer install.');
}

require $autoloadPath;

Dotenv::createImmutable($basePath)->safeLoad();

$config = [
    'app' => require $basePath . '/config/app.php',
    'database' => require $basePath . '/config/database.php',
];

$logger = new Logger((string) $config['app']['name']);
$logger->pushHandler(new StreamHandler(
    (string) $config['app']['log']['path'],
    (int) $config['app']['log']['level']
));

$container = new Container();
$container->instance(Logger::class, $logger);
$container->instance('config', (object) $config);

$app = new App(
    $config,
    $container,
    $logger,
    [
        new AppServiceProvider($container, $config),
    ]
);

$app->boot();

set_exception_handler(function (Throwable $exception) use ($container): void {
    $container->make(ExceptionHandler::class)->handle($exception)->send();
});

return $app;
