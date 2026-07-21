<?php

declare(strict_types=1);

use App\Core\App;
use Dotenv\Dotenv;

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

return App::create($config);
