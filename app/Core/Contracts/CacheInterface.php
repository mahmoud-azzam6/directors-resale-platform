<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Defines operations for a key-value cache store.
 */
interface CacheInterface
{
    /**
     * Retrieve a cached value.
     *
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value in the cache.
     *
     * @param mixed $value
     */
    public function put(string $key, mixed $value, ?int $ttl = null): void;

    /**
     * Determine whether a cache key exists.
     */
    public function has(string $key): bool;

    /**
     * Remove a value from the cache.
     */
    public function forget(string $key): void;

    /**
     * Remove all cached values.
     */
    public function clear(): void;
}
