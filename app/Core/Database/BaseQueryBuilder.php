<?php

declare(strict_types=1);

namespace App\Core\Database;

use InvalidArgumentException;
use PDO;
use PDOStatement;
use RuntimeException;

/**
 * Builds and executes parameterized SQL statements for a database connection.
 */
final class BaseQueryBuilder implements QueryBuilderInterface
{
    /**
     * @var array<int, string>
     */
    private array $columns = ['*'];

    /**
     * @var array<int, array{boolean: string, sql: string}>
     */
    private array $conditions = [];

    /**
     * @var array<string, mixed>
     */
    private array $bindings = [];

    /**
     * @var array<int, string>
     */
    private array $orders = [];

    private ?string $table = null;

    private ?int $limitValue = null;

    private ?int $offsetValue = null;

    private int $bindingIndex = 0;

    public function __construct(private DatabaseConnectionInterface $database)
    {
    }

    public function table(string $table): self
    {
        $this->table = $this->quoteIdentifier($table);

        return $this;
    }

    public function select(array $columns = ['*']): self
    {
        if ($columns === []) {
            throw new InvalidArgumentException('At least one column must be selected.');
        }

        $this->columns = array_map(
            fn (string $column): string => $column === '*' ? $column : $this->quoteIdentifier($column),
            $columns
        );

        return $this;
    }

    public function where(string $field, string $operator, mixed $value): self
    {
        return $this->addWhere('AND', $field, $operator, $value);
    }

    public function orWhere(string $field, string $operator, mixed $value): self
    {
        return $this->addWhere('OR', $field, $operator, $value);
    }

    public function whereIn(string $field, array $values): self
    {
        $quotedField = $this->quoteIdentifier($field);

        if ($values === []) {
            $this->conditions[] = ['boolean' => 'AND', 'sql' => '1 = 0'];

            return $this;
        }

        $placeholders = [];

        foreach ($values as $value) {
            $placeholder = $this->addBinding($value);
            $placeholders[] = $placeholder;
        }

        $this->conditions[] = [
            'boolean' => 'AND',
            'sql' => sprintf('%s IN (%s)', $quotedField, implode(', ', $placeholders)),
        ];

        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);

        if (! in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException('Sort direction must be ASC or DESC.');
        }

        $this->orders[] = sprintf('%s %s', $this->quoteIdentifier($field), $direction);

        return $this;
    }

    public function limit(int $limit): self
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('Limit must be zero or greater.');
        }

        $this->limitValue = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset must be zero or greater.');
        }

        $this->offsetValue = $offset;

        return $this;
    }

    public function first(): ?array
    {
        $this->limitValue = 1;
        $rows = $this->get();

        return $rows[0] ?? null;
    }

    public function get(): array
    {
        try {
            $sql = sprintf(
                'SELECT %s FROM %s%s%s%s',
                implode(', ', $this->columns),
                $this->requireTable(),
                $this->compileWhere(),
                $this->compileOrderBy(),
                $this->compileLimitOffset()
            );

            return $this->execute($sql)->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            $this->reset();
        }
    }

    public function insert(array $data): int
    {
        if ($data === []) {
            throw new InvalidArgumentException('Insert data cannot be empty.');
        }

        try {
            $columns = [];
            $placeholders = [];

            foreach ($data as $column => $value) {
                $columns[] = $this->quoteIdentifier($column);
                $placeholders[] = $this->addBinding($value);
            }

            $sql = sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $this->requireTable(),
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            $this->execute($sql);

            return (int) $this->database->connection()->lastInsertId();
        } finally {
            $this->reset();
        }
    }

    public function update(array $data): int
    {
        if ($data === []) {
            throw new InvalidArgumentException('Update data cannot be empty.');
        }

        try {
            $assignments = [];

            foreach ($data as $column => $value) {
                $assignments[] = sprintf('%s = %s', $this->quoteIdentifier($column), $this->addBinding($value));
            }

            $sql = sprintf(
                'UPDATE %s SET %s%s',
                $this->requireTable(),
                implode(', ', $assignments),
                $this->compileWhere()
            );

            return $this->execute($sql)->rowCount();
        } finally {
            $this->reset();
        }
    }

    public function delete(): int
    {
        try {
            $sql = sprintf('DELETE FROM %s%s', $this->requireTable(), $this->compileWhere());

            return $this->execute($sql)->rowCount();
        } finally {
            $this->reset();
        }
    }

    private function addWhere(string $boolean, string $field, string $operator, mixed $value): self
    {
        $operator = strtoupper($operator);
        $allowedOperators = ['=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE'];

        if (! in_array($operator, $allowedOperators, true)) {
            throw new InvalidArgumentException('Unsupported comparison operator.');
        }

        $this->conditions[] = [
            'boolean' => $boolean,
            'sql' => sprintf('%s %s %s', $this->quoteIdentifier($field), $operator, $this->addBinding($value)),
        ];

        return $this;
    }

    private function addBinding(mixed $value): string
    {
        $placeholder = ':binding_' . $this->bindingIndex++;
        $this->bindings[$placeholder] = $value;

        return $placeholder;
    }

    private function compileWhere(): string
    {
        if ($this->conditions === []) {
            return '';
        }

        $sql = ' WHERE ';

        foreach ($this->conditions as $index => $condition) {
            $sql .= $index === 0 ? '' : ' ' . $condition['boolean'] . ' ';
            $sql .= $condition['sql'];
        }

        return $sql;
    }

    private function compileOrderBy(): string
    {
        return $this->orders === [] ? '' : ' ORDER BY ' . implode(', ', $this->orders);
    }

    private function compileLimitOffset(): string
    {
        $sql = $this->limitValue === null ? '' : ' LIMIT ' . $this->limitValue;

        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return $sql;
    }

    private function execute(string $sql): PDOStatement
    {
        $statement = $this->database->connection()->prepare($sql);

        if ($statement === false) {
            throw new RuntimeException('Unable to prepare database statement.');
        }

        foreach ($this->bindings as $placeholder => $value) {
            $statement->bindValue($placeholder, $value, $this->bindingType($value));
        }

        $statement->execute();

        return $statement;
    }

    private function bindingType(mixed $value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            $value === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }

    private function quoteIdentifier(string $identifier): string
    {
        $segments = explode('.', $identifier);

        foreach ($segments as $segment) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $segment) !== 1) {
                throw new InvalidArgumentException('Invalid SQL identifier.');
            }
        }

        return implode('.', array_map(static fn (string $segment): string => '`' . $segment . '`', $segments));
    }

    private function requireTable(): string
    {
        if ($this->table === null) {
            throw new RuntimeException('A database table must be specified before executing a query.');
        }

        return $this->table;
    }

    private function reset(): void
    {
        $this->columns = ['*'];
        $this->conditions = [];
        $this->bindings = [];
        $this->orders = [];
        $this->table = null;
        $this->limitValue = null;
        $this->offsetValue = null;
        $this->bindingIndex = 0;
    }
}
