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
        $this->connection()->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection()->commit();
    }

    public function rollback(): void
    {
        $this->connection()->rollBack();
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $exception) {
            if ($this->connection()->inTransaction()) {
                $this->rollback();
            }

            throw $exception;
        }
    }
}
