<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'Directors Resale Platform',
    'version' => $_ENV['APP_VERSION'] ?? '0.1.0',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? '',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'base_path' => $_ENV['APP_BASE_PATH'] ?? '',
    'log' => [
        'path' => dirname(__DIR__) . '/storage/logs/app.log',
        'level' => match (strtolower($_ENV['APP_LOG_LEVEL'] ?? 'debug')) {
            'debug' => 100,
            'info' => 200,
            'notice' => 250,
            'warning' => 300,
            'error' => 400,
            'critical' => 500,
            'alert' => 550,
            'emergency' => 600,
            default => 100,
        },
    ],
];
