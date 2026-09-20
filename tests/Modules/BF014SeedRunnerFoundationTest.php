<?php
declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Modules\Property\Services\PropertyCatalogSeedPackage;
use App\Modules\Property\Services\PropertyCatalogSeedRunner;
use App\Providers\AppServiceProvider;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function seedCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function seedError(callable $call, string $message): void
{
    try { $call(); } catch (RuntimeException $e) {
        seedCheck($e->getMessage() === $message, 'Unexpected error: ' . $e->getMessage());
        return;
    }
    throw new RuntimeException('Expected ' . $message);
}

$root = dirname(__DIR__, 2);
Dotenv\Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$connection = $config['connections']['mysql'];
$name = 'directors_resale_platform_seed_foundation_test_' . getmypid();
seedCheck(preg_match('/^directors_resale_platform_seed_foundation_test_[0-9]+$/D', $name) === 1 && $name !== $connection['database'], 'Unsafe database');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
$db = null;
try {
    seedCheck(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB required');
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $config['connections']['mysql']['database'] = $name;
    $db = new DatabaseManager($config);
    $pdo = $db->connection();
    $files = glob($root . '/database/migrations/*.sql');
    sort($files);
    foreach ($files as $file) { $pdo->exec(file_get_contents($file)); }
    $pdo->exec('CREATE TABLE test_seed_writes (marker INT PRIMARY KEY) ENGINE=InnoDB');
    $container = new Container();
    (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $db);
    $runner = $container->make(PropertyCatalogSeedRunner::class);
    $ledger = fn(): array => $pdo->query('SELECT seed_key, checksum, applied_at FROM property_catalog_seed_versions ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    seedCheck($runner->run([]) === [] && $ledger() === [], 'Empty registry mutated ledger');
    $executed = [];
    $package = function (string $key, int $order, bool $fail = false) use ($pdo, &$executed): PropertyCatalogSeedPackage {
        return new PropertyCatalogSeedPackage($key, $order, 'test-content-' . $key, function () use ($pdo, &$executed, $order, $fail): void {
            seedCheck($pdo->inTransaction(), 'Callback must run inside transaction');
            $executed[] = $order;
            $pdo->exec('INSERT INTO test_seed_writes(marker) VALUES (' . $order . ')');
            if ($fail) { throw new RuntimeException('TEST_PACKAGE_FAILURE'); }
        });
    };
    $first = $package('TEST_ONLY_FIRST', 10);
    $second = $package('TEST_ONLY_SECOND', 20);
    seedError(fn() => $runner->run([$first, $package('TEST_ONLY_FIRST', 30)]), 'SEED_PACKAGE_DUPLICATE');
    seedError(fn() => $runner->run([$first, $package('TEST_ONLY_OTHER', 10)]), 'SEED_PACKAGE_DUPLICATE');
    seedCheck($executed === [] && $ledger() === [], 'Duplicate registration executed callbacks');
    $runner->run([$second, $first]);
    seedCheck($executed === [10, 20], 'Numeric order not respected');
    $rows = $ledger();
    seedCheck(count($rows) === 2, 'Expected one ledger record per package');
    foreach ([$first, $second] as $i => $p) {
        seedCheck($rows[$i]['seed_key'] === $p->key && $rows[$i]['checksum'] === hash('sha256', $p->content) && !empty($rows[$i]['applied_at']), 'Ledger key/checksum/timestamp');
    }
    $skips = $runner->run([$second, $first]);
    seedCheck($skips === ['SKIP TEST_ONLY_FIRST', 'SKIP TEST_ONLY_SECOND'] && $executed === [10, 20] && $ledger() === $rows, 'Rerun must skip without writes');
    $mismatchCalled = false;
    $changed = new PropertyCatalogSeedPackage($first->key, 10, 'changed', function () use (&$mismatchCalled): void { $mismatchCalled = true; });
    seedError(fn() => $runner->run([$changed]), 'SEED_PACKAGE_CHECKSUM_MISMATCH');
    seedCheck(!$mismatchCalled && $ledger() === $rows, 'Checksum mismatch executed or mutated');
    $earlier = $package('TEST_ONLY_EARLIER', 30);
    $failed = $package('TEST_ONLY_FAILED', 40, true);
    $later = $package('TEST_ONLY_LATER', 50);
    seedError(fn() => $runner->run([$later, $failed, $earlier]), 'TEST_PACKAGE_FAILURE');
    seedCheck(!$pdo->inTransaction(), 'Failed transaction left open');
    seedCheck($executed === [10, 20, 30, 40], 'Later package executed after failure');
    seedCheck($pdo->query('SELECT marker FROM test_seed_writes ORDER BY marker')->fetchAll(PDO::FETCH_COLUMN) == [10, 20, 30], 'Failure rollback or earlier commit lost');
    seedCheck(array_column($ledger(), 'seed_key') === ['TEST_ONLY_FIRST', 'TEST_ONLY_SECOND', 'TEST_ONLY_EARLIER'], 'Failed package recorded in ledger');
    $fixed = $package('TEST_ONLY_FAILED', 40);
    $retry = $runner->run([$later, $fixed, $earlier]);
    seedCheck($retry[0] === 'SKIP TEST_ONLY_EARLIER' && $executed === [10, 20, 30, 40, 40, 50], 'Retry did not skip/continue correctly');
    seedCheck($pdo->query('SELECT marker FROM test_seed_writes ORDER BY marker')->fetchAll(PDO::FETCH_COLUMN) == [10, 20, 30, 40, 50], 'Retry writes incorrect');
    seedCheck(array_column($ledger(), 'seed_key') === ['TEST_ONLY_FIRST', 'TEST_ONLY_SECOND', 'TEST_ONLY_EARLIER', 'TEST_ONLY_FAILED', 'TEST_ONLY_LATER'], 'Final ledger incorrect');
    echo "BF014 seed runner foundation MariaDB acceptance: PASS\n";
} finally {
    if ($db !== null && $db->connection()->inTransaction()) { $db->rollback(); }
    if ($created) { $server->exec("DROP DATABASE `$name`"); }
}
