<?php

declare(strict_types=1);

namespace App\Core\Database;

/**
 * Represents a field comparison used to filter query results.
 */
final class Filter
{
    private string $field;

    private string $operator;

    private mixed $value;

    public function __construct(string $field, string $operator, mixed $value)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function field(): string
    {
        return $this->field;
    }

    public function operator(): string
    {
        return $this->operator;
    }

    public function value(): mixed
    {
        return $this->value;
    }
}
