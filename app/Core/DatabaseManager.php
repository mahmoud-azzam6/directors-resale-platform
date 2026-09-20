<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Database\DatabaseConnectionInterface;
use PDO;
use RuntimeException;

final class DatabaseManager implements DatabaseConnectionInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    private ?PDO $connection = null;

    private int $transactionDepth = 0;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getConnection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $connectionName = (string) ($this->config['default'] ?? 'mysql');
        $connections = $this->config['connections'] ?? [];

        if (! isset($connections[$connectionName])) {
            throw new RuntimeException("Database connection {$connectionName} is not configured.");
        }

        $connection = $connections[$connectionName];

        if (($connection['driver'] ?? '') !== 'mysql') {
            throw new RuntimeException('Only mysql connections are currently supported.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $connection['host'],
            $connection['port'],
            $connection['database'],
            $connection['charset']
        );

        $this->connection = new PDO(
            $dsn,
            (string) $connection['username'],
            (string) $connection['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return $this->connection;
    }

    public function connection(): PDO
    {
        return $this->getConnection();
    }

    public function beginTransaction(): void
    {
        $connection = $this->connection();
        if (!$connection->inTransaction()) {
            $this->transactionDepth = 0;
        }

        if ($this->transactionDepth === 0) {
            $connection->beginTransaction();
        } else {
            $connection->exec('SAVEPOINT database_manager_' . ($this->transactionDepth + 1));
        }
        ++$this->transactionDepth;
    }

    public function commit(): void
    {
        try {
            if ($this->transactionDepth > 1) {
                $this->connection()->exec('RELEASE SAVEPOINT database_manager_' . $this->transactionDepth);
                --$this->transactionDepth;
            } else {
                $this->connection()->commit();
                $this->transactionDepth = 0;
            }
        } finally {
            if (!$this->connection()->inTransaction()) {
                $this->transactionDepth = 0;
            }
        }
    }

    public function rollback(): void
    {
        try {
            if ($this->transactionDepth > 1) {
                $savepoint = 'database_manager_' . $this->transactionDepth;
                $this->connection()->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
                $this->connection()->exec('RELEASE SAVEPOINT ' . $savepoint);
                --$this->transactionDepth;
            } else {
                $this->connection()->rollBack();
                $this->transactionDepth = 0;
            }
        } finally {
            if (!$this->connection()->inTransaction()) {
                $this->transactionDepth = 0;
            }
        }
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        $scopeDepth = $this->transactionDepth;

        try {
            $result = $callback($this);
            if ($this->transactionDepth !== $scopeDepth) {
                throw new RuntimeException('Unbalanced transaction scope.');
            }
            $this->commit();

            return $result;
        } catch (\Throwable $exception) {
            while ($this->connection()->inTransaction() && $this->transactionDepth >= $scopeDepth) {
                $this->rollback();
            }

            if (!$this->connection()->inTransaction()) {
                $this->transactionDepth = 0;
            }

            throw $exception;
        }
    }
}
