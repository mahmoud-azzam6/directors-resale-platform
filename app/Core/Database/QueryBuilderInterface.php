<?php

declare(strict_types=1);

namespace App\Core\Database;

/**
 * Defines fluent, database-agnostic query construction operations.
 */
interface QueryBuilderInterface
{
    public function table(string $table): self;

    /**
     * @param array<int, string> $columns
     */
    public function select(array $columns = ['*']): self;

    public function where(string $field, string $operator, mixed $value): self;

    public function orWhere(string $field, string $operator, mixed $value): self;

    /**
     * @param array<int, mixed> $values
     */
    public function whereIn(string $field, array $values): self;

    public function orderBy(string $field, string $direction = 'ASC'): self;

    public function limit(int $limit): self;

    public function offset(int $offset): self;

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(): array;

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int;

    /**
     * @param array<string, mixed> $data
     */
    public function update(array $data): int;

    public function delete(): int;
}
