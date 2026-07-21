<?php

declare(strict_types=1);

namespace App\Core;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

final class App
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    private Logger $logger;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        array $config,
        Logger $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config): self
    {
        $logger = new Logger((string) ($config['app']['name'] ?? 'directors-resale-platform'));
        $logConfig = $config['app']['log'] ?? [];
        $logPath = (string) ($logConfig['path'] ?? dirname(__DIR__, 2) . '/storage/logs/app.log');
        $logLevel = self::resolveLogLevel((string) ($logConfig['level'] ?? 'debug'));

        $logger->pushHandler(new StreamHandler($logPath, $logLevel));

        return new self($config, $logger);
    }

    public function boot(): void
    {
        date_default_timezone_set((string) ($this->config['app']['timezone'] ?? 'UTC'));

        $this->logger->info('Application bootstrapped.');
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->config;
    }

    public function logger(): Logger
    {
        return $this->logger;
    }

    private static function resolveLogLevel(string $level): int
    {
        return match (strtolower($level)) {
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'notice' => Logger::NOTICE,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
            'alert' => Logger::ALERT,
            'emergency' => Logger::EMERGENCY,
            default => Logger::DEBUG,
        };
    }
}
