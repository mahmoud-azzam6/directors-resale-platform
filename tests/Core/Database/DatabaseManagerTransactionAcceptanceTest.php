<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Providers\AppServiceProvider;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

function transactionCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$root = dirname(__DIR__, 3);
Dotenv\Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$c = $config['connections']['mysql'];
$name = 'directors_resale_platform_transaction_test_' . getmypid();
transactionCheck(preg_match('/^directors_resale_platform_transaction_test_[0-9]+$/D', $name) === 1 && $name !== $c['database'], 'Unsafe database');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $c['host'], $c['port']), $c['username'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
$db = null;
try {
    transactionCheck(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB required');
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $config['connections']['mysql']['database'] = $name;
    $db = new DatabaseManager($config);
    $pdo = $db->connection();
    $files = glob($root . '/database/migrations/*.sql');
    sort($files);
    foreach ($files as $file) { $pdo->exec(file_get_contents($file)); }
    $pdo->exec('CREATE TABLE transaction_markers (id INT PRIMARY KEY) ENGINE=InnoDB');
    $write = fn(int $id) => $pdo->exec('INSERT INTO transaction_markers VALUES (' . $id . ')');
    $rows = fn(): array => array_map('intval', $pdo->query('SELECT id FROM transaction_markers ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
    $clean = function () use ($db, $pdo): void {
        transactionCheck(!$pdo->inTransaction(), 'Transaction left active');
        $db->beginTransaction();
        $db->rollback();
        transactionCheck(!$pdo->inTransaction(), 'Depth not reset');
    };

    $db->beginTransaction(); $write(1); $db->commit();
    transactionCheck($rows() === [1], 'Single commit'); $clean();
    $db->beginTransaction(); $write(2); $db->rollback();
    transactionCheck($rows() === [1], 'Single rollback'); $clean();
    $db->beginTransaction(); $write(2);
    $db->beginTransaction(); $write(3); $db->commit();
    transactionCheck($pdo->inTransaction(), 'Inner commit ended outer transaction');
    $db->rollback();
    transactionCheck($rows() === [1], 'Nested commit / outer rollback'); $clean();
    $db->beginTransaction(); $write(2);
    $db->beginTransaction(); $write(3); $db->commit(); $db->commit();
    transactionCheck($rows() === [1, 2, 3], 'Nested commit / outer commit'); $clean();
    $db->beginTransaction(); $write(4);
    $db->beginTransaction(); $write(5); $db->rollback();
    transactionCheck($pdo->inTransaction(), 'Inner rollback ended outer transaction');
    $write(6); $db->commit();
    transactionCheck($rows() === [1, 2, 3, 4, 6], 'Nested rollback / continuation'); $clean();

    $result = $db->transaction(function () use ($db, $write): string {
        $write(7);
        try {
            $db->transaction(function () use ($db, $write): void {
                $write(8);
                $db->transaction(fn() => $write(9));
                throw new RuntimeException('INNER_FAILURE');
            });
        } catch (RuntimeException $e) {
            transactionCheck($e->getMessage() === 'INNER_FAILURE', 'Original exception lost');
        }
        $write(10);
        return 'result';
    });
    transactionCheck($result === 'result' && $rows() === [1, 2, 3, 4, 6, 7, 10], 'Three depths / exception continuation'); $clean();
    try {
        $db->transaction(function () use ($db, $write): void {
            $write(11);
            $db->transaction(fn() => $write(1));
        });
        throw new RuntimeException('Expected duplicate failure');
    } catch (PDOException $e) {
        transactionCheck($e->getCode() === '23000', 'Unexpected SQL error');
    }
    transactionCheck($rows() === [1, 2, 3, 4, 6, 7, 10], 'Uncaught failure rollback'); $clean();

    try {
        $db->transaction(function () use ($db, $write): void {
            $db->beginTransaction();
            $write(11);
        });
        throw new RuntimeException('Expected unbalanced scope failure');
    } catch (RuntimeException $e) {
        transactionCheck($e->getMessage() === 'Unbalanced transaction scope.', 'Unexpected scope error');
    }
    transactionCheck($rows() === [1, 2, 3, 4, 6, 7, 10], 'Unbalanced scope leaked writes'); $clean();

    // A database-aborted transaction has no savepoints left to unwind.
    try {
        $db->transaction(function () use ($db, $pdo, $write): void {
            $db->transaction(function () use ($pdo, $write): void {
                $write(11);
                $pdo->rollBack();
                throw new RuntimeException('TRANSACTION_ABORTED');
            });
        });
    } catch (RuntimeException $e) {
        transactionCheck($e->getMessage() === 'TRANSACTION_ABORTED', 'Abort error replaced during cleanup');
    }
    transactionCheck($rows() === [1, 2, 3, 4, 6, 7, 10], 'Aborted transaction leaked writes'); $clean();

    $box = new Container();
    (new AppServiceProvider($box, []))->register();
    $box->instance(DatabaseConnectionInterface::class, $db);
    $catalog = $box->make(PropertyCatalogService::class);
    $category = $catalog->createCategory(['code'=>'TX_CATEGORY', 'name_ar'=>'Test', 'name_en'=>'Test']);
    $data = ['code'=>'TX_UNIT', 'name_ar'=>'Test', 'name_en'=>'Test', 'property_category_id'=>$category['id']];
    $db->beginTransaction();
    $catalog->createUnitType($data);
    $db->rollback();
    transactionCheck((int) $pdo->query('SELECT COUNT(*) FROM unit_types')->fetchColumn() === 0, 'Service writes survived outer rollback'); $clean();
    $db->beginTransaction();
    $catalog->createUnitType($data);
    $db->commit();
    transactionCheck((int) $pdo->query('SELECT COUNT(*) FROM unit_types')->fetchColumn() === 1, 'Service writes did not persist'); $clean();
    echo "DatabaseManager transaction composition MariaDB acceptance: PASS\n";
} finally {
    if ($db !== null && $db->connection()->inTransaction()) { $db->connection()->rollBack(); }
    if ($created) { $server->exec("DROP DATABASE `$name`"); }
}
