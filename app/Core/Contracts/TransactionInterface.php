<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Defines database transaction lifecycle operations.
 */
interface TransactionInterface
{
    public function transaction(callable $callback);

    /**
     * Begin a transaction.
     */
    public function begin(): void;

    /**
     * Commit the active transaction.
     */
    public function commit(): void;

    /**
     * Roll back the active transaction.
     */
    public function rollback(): void;
}
