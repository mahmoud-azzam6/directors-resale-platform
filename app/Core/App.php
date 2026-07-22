<?php

declare(strict_types=1);

namespace App\Core;

use Monolog\Logger;

final class App
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    private Container $container;

    private Logger $logger;

    /**
     * @param array<string, mixed> $config
     * @param array<int, ServiceProvider> $providers
     */
    public function __construct(
        array $config,
        Container $container,
        Logger $logger,
        private array $providers = []
    ) {
        $this->config = $config;
        $this->container = $container;
        $this->logger = $logger;
    }


    public function boot(): void
    {
        date_default_timezone_set((string) ($this->config['app']['timezone'] ?? 'UTC'));

        foreach ($this->providers as $provider) {
            $provider->register();
        }

        $this->logger->info('Application bootstrapped.');
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->config;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function logger(): Logger
    {
        return $this->logger;
    }
}
