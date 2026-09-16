<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\PropertyCatalogRepository;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Modules\Property\Services\UnitTypeConfigurationService;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function c5aAssert(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
}

/** @return array{result:string,code?:string,error?:string} */
function c5aWorker(string $root, string $databaseName, int $unitTypeId): array
{
    $config = require $root . '/config/database.php';
    $config['connections']['mysql']['database'] = $databaseName;
    $database = new DatabaseManager($config);
    $database->connection()->exec('SET SESSION innodb_lock_wait_timeout = 5');
    $container = new Container();
    (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $database);
    $service = $container->make(UnitTypeConfigurationService::class);
    try {
        $draft = $service->createBlankDraft($unitTypeId);
        return ['result' => 'ok', 'version' => (string) $draft['version_number']];
    } catch (ValidationException $exception) {
        return ['result' => 'validation', 'code' => (string) array_key_first($exception->errors())];
    } catch (Throwable $exception) {
        return ['result' => 'error', 'error' => $exception::class . ': ' . $exception->getMessage()];
    }
}

/** @param array<int,resource> $pipes @return resource */
function c5aStartWorker(string $databaseName, int $unitTypeId, array &$pipes): mixed
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__)
        . ' --c5a-worker ' . escapeshellarg($databaseName) . ' ' . $unitTypeId;
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));
    if (! is_resource($process)) { throw new RuntimeException('Unable to start worker.'); }
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    return $process;
}

/** @param resource $process @param array<int,resource> $pipes @return array{result:string,code?:string,error?:string,version?:string} */
function c5aFinishWorker(mixed $process, array $pipes, float $timeout): array
{
    $deadline = microtime(true) + $timeout;
    while (proc_get_status($process)['running']) {
        if (microtime(true) >= $deadline) {
            proc_terminate($process);
            throw new RuntimeException('Worker timed out.');
        }
        usleep(20_000);
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    $result = json_decode(trim($stdout), true);
    if ($exit !== 0 || ! is_array($result)) {
        throw new RuntimeException('Worker failed: ' . trim($stderr . ' ' . $stdout));
    }
    return $result;
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
if (($argv[1] ?? null) === '--c5a-worker') {
    $result = c5aWorker($root, (string) ($argv[2] ?? ''), (int) ($argv[3] ?? 0));
    echo json_encode($result, JSON_THROW_ON_ERROR);
    exit($result['result'] === 'error' ? 1 : 0);
}

$config = require $root . '/config/database.php';
$connection = $config['connections']['mysql'];
$developmentDatabase = (string) $connection['database'];
$databaseName = 'directors_resale_platform_c5a_test_' . getmypid();
c5aAssert(preg_match('/^directors_resale_platform_c5a_test_[0-9]+$/D', $databaseName) === 1
    && $databaseName !== $developmentDatabase, 'Unsafe disposable database name');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']),
    (string) $connection['username'], (string) $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
$database = null;
$workerA = $workerB = null;
$aPipes = $bPipes = [];
try {
    c5aAssert(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB required');
    $server->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $config['connections']['mysql']['database'] = $databaseName;
    $database = new DatabaseManager($config);
    $pdo = $database->connection();
    $migrations = glob($root . '/database/migrations/*.sql') ?: [];
    sort($migrations, SORT_STRING);
    foreach ($migrations as $migration) { $pdo->exec(file_get_contents($migration)); }

    $container = new Container();
    (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $database);
    $catalog = $container->make(PropertyCatalogService::class);
    $units = $container->make(PropertyCatalogRepository::class);
    $configs = $container->make(\App\Modules\Property\Repositories\UnitTypeConfigurationRepository::class);
    $category = $catalog->createCategory(['code' => 'C5A', 'name_ar' => 'C5A', 'name_en' => 'C5A']);
    $unit = $catalog->createUnitType([
        'property_category_id' => $category['id'], 'code' => 'C5A', 'name_ar' => 'C5A', 'name_en' => 'C5A',
    ]);
    c5aAssert($unit['last_allocated_configuration_version'] === 0, 'Initial allocation counter');

    $lockName = 'bf014_c5a_draft_' . getmypid();
    $pdo->exec("CREATE TRIGGER c5a_pause BEFORE UPDATE ON unit_types FOR EACH ROW
        BEGIN
            IF NEW.id = {$unit['id']} AND NEW.last_allocated_configuration_version = 1 THEN
                DO GET_LOCK('{$lockName}', 0);
                DO SLEEP(2);
            END IF;
        END");

    $startedA = microtime(true);
    $workerA = c5aStartWorker($databaseName, $unit['id'], $aPipes);
    $deadline = microtime(true) + 3.0;
    $owner = null;
    do {
        $statement = $server->prepare('SELECT IS_USED_LOCK(?)');
        $statement->execute([$lockName]);
        $owner = $statement->fetchColumn();
        if ($owner !== null) { break; }
        usleep(20_000);
    } while (microtime(true) < $deadline);
    c5aAssert($owner !== null, 'Service A did not reach allocation update while holding Unit Type lock');

    $startedB = microtime(true);
    $workerB = c5aStartWorker($databaseName, $unit['id'], $bPipes);
    $resultA = c5aFinishWorker($workerA, $aPipes, 6.0);
    $workerA = null;
    $resultB = c5aFinishWorker($workerB, $bPipes, 6.0);
    $workerB = null;

    c5aAssert($resultA['result'] === 'ok' && ($resultA['version'] ?? null) === '1', 'Service A result');
    c5aAssert(($resultB['result'] ?? null) === 'validation'
        && ($resultB['code'] ?? null) === 'CONFIGURATION_DRAFT_ALREADY_EXISTS', 'Service B business result');
    $elapsedB = microtime(true) - $startedB;
    c5aAssert($elapsedB >= 0.5, 'Service B did not wait for Unit Type serialization');
    $drafts = $configs->listConfigurationsForUnitType($unit['id'], ['status' => 'draft']);
    c5aAssert(count($drafts) === 1 && $drafts[0]['version_number'] === 1, 'Exactly one version-one DRAFT');
    c5aAssert($units->findUnitTypeById($unit['id'])['last_allocated_configuration_version'] === 1, 'Allocation counter is one');
    c5aAssert($configs->findConfigurationByVersion($unit['id'], 2) === null, 'No version two allocated');
    $guard = $pdo->query("SHOW CREATE TABLE unit_type_configuration_versions")->fetch(PDO::FETCH_ASSOC)['Create Table'];
    c5aAssert(str_contains($guard, 'uq_unit_type_configs_draft_guard'), 'One-DRAFT unique guard remains');
    echo "C5a real concurrent draft creation: PASS; B_wait_seconds=" . number_format($elapsedB, 3) . "\n";
} finally {
    foreach ([[$workerA, $aPipes], [$workerB, $bPipes]] as [$worker, $pipes]) {
        if ($worker !== null) {
            proc_terminate($worker);
            foreach ($pipes as $pipe) { fclose($pipe); }
            proc_close($worker);
        }
    }
    if ($database !== null && $database->connection()->inTransaction()) { $database->rollback(); }
    if ($created) { $server->exec("DROP DATABASE `{$databaseName}`"); }
}
