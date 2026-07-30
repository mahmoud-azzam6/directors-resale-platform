<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Defines application logging operations.
 */
interface LoggerInterface
{
    /**
     * Record an informational log entry.
     *
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * Record a warning log entry.
     *
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Record an error log entry.
     *
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void;
}
