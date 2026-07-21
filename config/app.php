<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'Directors Resale Platform',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? '',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'log' => [
        'path' => dirname(__DIR__) . '/storage/logs/app.log',
        'level' => $_ENV['APP_LOG_LEVEL'] ?? 'debug',
    ],
];
