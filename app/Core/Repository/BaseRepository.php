<?php

declare(strict_types=1);

namespace App\Core\Repository;

use App\Core\Contracts\CrudRepositoryInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\Database\Filter;
use App\Core\Database\QueryBuilderInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Provides reusable CRUD operations for table-backed repositories.
 *
 * Child repositories supply only the table and primary-key metadata.
 */
abstract class BaseRepository implements CrudRepositoryInterface
{
    protected DatabaseConnectionInterface $database;

    protected QueryBuilderInterface $queryBuilder;

    public function __construct(
        DatabaseConnectionInterface $database,
        QueryBuilderInterface $queryBuilder
    ) {
        $this->database = $database;
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Return the table managed by this repository.
     */
    abstract protected function table(): string;

    /**
     * Return the primary-key column managed by this repository.
     */
    abstract protected function primaryKey(): string;

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array
    {
        return $this->query()
            ->where($this->primaryKey(), '=', $id)
            ->first();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function all(array $filters = []): array
    {
        $query = $this->query();

        foreach ($filters as $field => $filter) {
            if ($filter instanceof Filter) {
                $query->where($filter->field(), $filter->operator(), $filter->value());

                continue;
            }

            if (! is_string($field)) {
                throw new InvalidArgumentException('Filters must use field names as keys or Filter instances.');
            }

            if (is_array($filter)) {
                $query->whereIn($field, $filter);

                continue;
            }

            $query->where($field, '=', $filter);
        }

        return $query->get();
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function create(array $attributes): array
    {
        $attributes = $this->beforeCreate($attributes);
        $id = $this->query()->insert($attributes);
        $record = $this->find($id);

        if ($record === null) {
            throw new RuntimeException('Created record could not be retrieved.');
        }

        $this->afterCreate($record);

        return $record;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>|null
     */
    public function update(int|string $id, array $attributes): ?array
    {
        $attributes = $this->beforeUpdate($attributes);

        $this->query()
            ->where($this->primaryKey(), '=', $id)
            ->update($attributes);

        $record = $this->find($id);

        if ($record !== null) {
            $this->afterUpdate($record);
        }

        return $record;
    }

    public function delete(int|string $id): bool
    {
        $this->beforeDelete($id);

        $deleted = $this->query()
            ->where($this->primaryKey(), '=', $id)
            ->delete() > 0;

        if ($deleted) {
            $this->afterDelete($id);
        }

        return $deleted;
    }

    /**
     * @return QueryBuilderInterface
     */
    protected function query(): QueryBuilderInterface
    {
        return $this->queryBuilder->table($this->table());
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function beforeCreate(array $data): array
    {
        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function beforeUpdate(array $data): array
    {
        return $data;
    }

    /**
     * @param array<string, mixed> $record
     */
    protected function afterCreate(array $record): void
    {
    }

    /**
     * @param array<string, mixed> $record
     */
    protected function afterUpdate(array $record): void
    {
    }

    protected function beforeDelete(int|string $id): void
    {
    }

    protected function afterDelete(int|string $id): void
    {
    }
}
