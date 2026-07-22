<?php

declare(strict_types=1);

namespace App\Core;

abstract class ServiceProvider
{
    protected Container $container;

    /**
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(Container $container, array $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    abstract public function register(): void;
}
