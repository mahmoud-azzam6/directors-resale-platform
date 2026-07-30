<?php

declare(strict_types=1);

namespace App\Core\Database;

/**
 * Represents a field and direction used to order query results.
 */
final class Sort
{
    private string $field;

    private string $direction;

    public function __construct(string $field, string $direction)
    {
        $this->field = $field;
        $this->direction = $direction;
    }

    public function field(): string
    {
        return $this->field;
    }

    public function direction(): string
    {
        return $this->direction;
    }
}
