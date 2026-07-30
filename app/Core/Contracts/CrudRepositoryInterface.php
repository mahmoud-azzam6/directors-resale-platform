<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Defines the common persistence operations for repositories.
 */
interface CrudRepositoryInterface
{
    /**
     * Find a record by its identifier.
     *
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array;

    /**
     * Retrieve all records, optionally filtered.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function all(array $filters = []): array;

    /**
     * Persist a new record.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function create(array $attributes): array;

    /**
     * Update an existing record.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>|null
     */
    public function update(int|string $id, array $attributes): ?array;

    /**
     * Delete a record by its identifier.
     */
    public function delete(int|string $id): bool;
}
