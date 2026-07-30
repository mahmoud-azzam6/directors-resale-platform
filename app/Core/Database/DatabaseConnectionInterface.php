<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;

/**
 * Defines access to a database connection and its transaction lifecycle.
 */
interface DatabaseConnectionInterface
{
    public function connection(): PDO;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollback(): void;

    public function transaction(callable $callback): mixed;
}
