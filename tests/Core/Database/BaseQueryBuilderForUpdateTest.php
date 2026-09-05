<?php

declare(strict_types=1);

use App\Core\Database\BaseQueryBuilder;
use App\Core\Database\DatabaseConnectionInterface;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

final class QueryBuilderSpyStatement extends PDOStatement
{
    /** @var array<int, array<string, mixed>> */
    private array $rows;

    /** @param array<int, array<string, mixed>> $rows */
    public function __construct(array $rows = [])
    {
        $this->rows = $rows;
    }

    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        return true;
    }

    public function execute(?array $params = null): bool
    {
        return true;
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        return $this->rows;
    }

    public function rowCount(): int
    {
        return 1;
    }
}

final class QueryBuilderSpyPdo extends PDO
{
    /** @var array<int, string> */
    public array $sql = [];

    /** @var array<int, array<string, mixed>> */
    public array $nextRows = [];

    public bool $failNextPrepare = false;

    public function __construct()
    {
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        if ($this->failNextPrepare) {
            $this->failNextPrepare = false;

            throw new RuntimeException('Simulated prepare failure.');
        }

        $this->sql[] = $query;

        return new QueryBuilderSpyStatement($this->nextRows);
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return '1';
    }
}

final class QueryBuilderSpyConnection implements DatabaseConnectionInterface
{
    public function __construct(private QueryBuilderSpyPdo $pdo)
    {
    }

    public function connection(): PDO
    {
        return $this->pdo;
    }

    public function beginTransaction(): void
    {
    }

    public function commit(): void
    {
    }

    public function rollback(): void
    {
    }

    public function transaction(callable $callback): mixed
    {
        return $callback($this);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            "%s\nExpected: %s\nActual: %s",
            $message,
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
}

$pdo = new QueryBuilderSpyPdo();
$query = new BaseQueryBuilder(new QueryBuilderSpyConnection($pdo));

$query->table('owners')->where('id', '=', 1)->get();
assertSameValue('SELECT * FROM `owners` WHERE `id` = :binding_0', $pdo->sql[0], 'Standard SELECT changed.');

$query
    ->table('owners')
    ->where('status', '=', 'active')
    ->orderBy('id', 'DESC')
    ->limit(5)
    ->forUpdate()
    ->get();
assertSameValue(
    'SELECT * FROM `owners` WHERE `status` = :binding_0 ORDER BY `id` DESC LIMIT 5 FOR UPDATE',
    $pdo->sql[1],
    'FOR UPDATE SELECT clause ordering is invalid.'
);

$pdo->nextRows = [['id' => 1]];
$row = $query->table('owners')->where('id', '=', 1)->forUpdate()->first();
assertSameValue(['id' => 1], $row, 'Locked first() did not return the first row.');
assertSameValue(
    'SELECT * FROM `owners` WHERE `id` = :binding_0 LIMIT 1 FOR UPDATE',
    $pdo->sql[2],
    'first() did not append FOR UPDATE after LIMIT 1.'
);

$query->table('owners')->get();
assertSameValue('SELECT * FROM `owners`', $pdo->sql[3], 'Lock state leaked to a later SELECT.');

$query->table('owners')->forUpdate()->insert(['display_name' => 'Owner']);
assertSameValue(
    'INSERT INTO `owners` (`display_name`) VALUES (:binding_0)',
    $pdo->sql[4],
    'INSERT SQL was altered by FOR UPDATE state.'
);

$query->table('owners')->forUpdate()->where('id', '=', 1)->update(['display_name' => 'Updated']);
assertSameValue(
    'UPDATE `owners` SET `display_name` = :binding_1 WHERE `id` = :binding_0',
    $pdo->sql[5],
    'UPDATE SQL was altered by FOR UPDATE state.'
);

$query->table('owners')->forUpdate()->where('id', '=', 1)->delete();
assertSameValue(
    'DELETE FROM `owners` WHERE `id` = :binding_0',
    $pdo->sql[6],
    'DELETE SQL was altered by FOR UPDATE state.'
);

$query->table('owners')->get();
assertSameValue('SELECT * FROM `owners`', $pdo->sql[7], 'Write execution did not reset lock state.');

$pdo->failNextPrepare = true;
try {
    $query->table('owners')->forUpdate()->get();
    throw new RuntimeException('Expected simulated prepare failure.');
} catch (RuntimeException $exception) {
    assertSameValue('Simulated prepare failure.', $exception->getMessage(), 'Unexpected SELECT failure.');
}

$query->table('owners')->get();
assertSameValue('SELECT * FROM `owners`', $pdo->sql[8], 'Failed SELECT did not reset lock state.');

echo "BaseQueryBuilder FOR UPDATE tests passed.\n";
