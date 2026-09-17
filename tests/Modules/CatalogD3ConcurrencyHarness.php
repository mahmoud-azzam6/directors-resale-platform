<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Exceptions\ValidationException;
use App\Modules\Property\Services\DevelopmentCatalogService;
use App\Modules\Property\Services\GeographicLocationService;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

function d3Assert(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

function d3Container(DatabaseManager $database): Container
{
    $container = new Container();
    (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $database);
    return $container;
}

function d3Data(string $code, array $extra = []): array
{
    return $extra + ['code' => $code, 'name_ar' => $code, 'name_en' => $code];
}

function d3Scalar(PDO $pdo, string $sql, array $parameters = []): mixed
{
    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);
    return $statement->fetchColumn();
}

function d3Worker(string $name, array $operation, string $marker): void
{
    $started = microtime(true);
    try {
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        d3Assert(preg_match('/^directors_resale_platform_d3[a-d]_test_[0-9]+$/D', $name) === 1 && $name !== $config['connections']['mysql']['database'], 'Unsafe worker database');
        $config['connections']['mysql']['database'] = $name;
        $database = new DatabaseManager($config);
        $database->connection()->exec('SET SESSION innodb_lock_wait_timeout = 10');
        $container = d3Container($database);
        $connectionId = (int) d3Scalar($database->connection(), 'SELECT CONNECTION_ID()');
        d3Assert((int) d3Scalar($database->connection(), 'SELECT GET_LOCK(?, 0)', [$marker]) === 1, 'Worker connection marker');
        $started = microtime(true);
        $method = $operation['method'];
        d3Assert(in_array($method, ['deactivateLocation', 'createLocation', 'deactivateDeveloper', 'createProject', 'deactivateProject', 'createProjectPhase', 'reactivateProjectPhase'], true), 'Unknown worker operation');
        $service = $container->make(in_array($method, ['deactivateLocation', 'createLocation'], true) ? GeographicLocationService::class : DevelopmentCatalogService::class);
        $service->$method(...$operation['args']);
        $result = ['result' => 'ok'];
    } catch (ValidationException $exception) {
        $result = ['result' => 'validation', 'code' => (string) array_key_first($exception->errors())];
    } catch (Throwable $exception) {
        $result = ['result' => 'error', 'error' => $exception::class . ': ' . $exception->getMessage()];
    }
    echo json_encode($result + ['event' => 'result', 'connection_id' => $connectionId ?? null, 'pid' => getmypid(), 'elapsed_seconds' => microtime(true) - $started], JSON_THROW_ON_ERROR) . "\n";
    exit($result['result'] === 'error' ? 1 : 0);
}

if (($argv[1] ?? null) === '--d3-worker') {
    d3Worker((string) $argv[2], json_decode(base64_decode((string) $argv[3], true), true, 512, JSON_THROW_ON_ERROR), (string) $argv[4]);
}

final class D3WorkerProcess
{
    private mixed $process;
    private array $pipes = [];
    private string $stdout = '';
    private string $stderr = '';
    public int $connectionId;
    public int $pid;

    public function __construct(string $entry, string $name, array $operation, private string $marker)
    {
        $command = [PHP_BINARY, $entry, '--d3-worker', $name, base64_encode(json_encode($operation, JSON_THROW_ON_ERROR)), $marker];
        $this->process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $this->pipes, dirname(__DIR__, 2), null, ['bypass_shell' => true]);
        d3Assert(is_resource($this->process), 'Worker launch failed');
        foreach ($this->pipes as $pipe) { stream_set_blocking($pipe, false); }
    }

    public function connected(PDO $server): void
    {
        // Windows process pipes cannot reliably be read non-blockingly while
        // a worker is paused. Discover its connection through a named marker.
        $deadline = microtime(true) + 5;
        do {
            $owner = d3Scalar($server, 'SELECT IS_USED_LOCK(?)', [$this->marker]);
            if ($owner !== null && $owner !== false) {
                $this->connectionId = (int) $owner;
                $this->pid = proc_get_status($this->process)['pid'];
                return;
            }
            usleep(10_000);
        } while (microtime(true) < $deadline);
        throw new RuntimeException('Worker did not publish its connection marker');
    }

    public function finish(): array
    {
        $deadline = microtime(true) + 12;
        do {
            $status = proc_get_status($this->process);
            if (!$status['running']) { break; }
            usleep(10_000);
        } while (microtime(true) < $deadline);
        d3Assert(!$status['running'], 'Worker failed to exit');
        $this->stdout .= stream_get_contents($this->pipes[1]);
        $this->stderr .= stream_get_contents($this->pipes[2]);
        $result = json_decode(trim($this->stdout), true);
        foreach ($this->pipes as $pipe) { fclose($pipe); }
        $this->pipes = [];
        $closed = proc_close($this->process);
        $this->process = null;
        $exit = $status['exitcode'] >= 0 ? $status['exitcode'] : $closed;
        d3Assert($exit === 0 && $this->stderr === '' && ($result['event'] ?? null) === 'result' && $result['result'] !== 'error', 'Raw worker/SQL failure: ' . json_encode($result) . $this->stderr . $this->stdout);
        d3Assert($result['connection_id'] === $this->connectionId && $result['pid'] === $this->pid, 'Worker process/connection identity mismatch');
        return $result;
    }

    public function cleanup(): void
    {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            foreach ($this->pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
            proc_close($this->process);
            $this->process = null;
        }
    }
}

/** Both operations use Services. Triggers only pause the winning write. */
function d3Race(PDO $server, PDO $pdo, string $entry, string $name, string $unit, array $fixture, bool $deactivateFirst): void
{
    $order = $deactivateFirst ? 'deactivate-first' : 'child-first';
    $gate = 'bf014_' . $unit . '_' . getmypid() . '_gate';
    $ready = 'bf014_' . $unit . '_' . getmypid() . '_ready';
    $trigger = 'd3_pause';
    $a = $b = null;
    $triggerMade = false;
    try {
        d3Assert((int) d3Scalar($server, 'SELECT GET_LOCK(?, 0)', [$gate]) === 1, 'Coordinator gate acquisition');
        $table = $deactivateFirst ? $fixture['parent_table'] : $fixture['child_table'];
        $event = $deactivateFirst || isset($fixture['child_id']) ? 'UPDATE' : 'INSERT';
        $predicate = $deactivateFirst
            ? 'NEW.id = ' . $fixture['parent_id'] . " AND NEW.status = 'inactive'"
            : (isset($fixture['child_id']) ? 'NEW.id = ' . $fixture['child_id'] . " AND NEW.status = 'active'" : 'NEW.code = ' . $pdo->quote($fixture['child_code']));
        $pdo->exec("CREATE TRIGGER `$trigger` BEFORE $event ON `$table` FOR EACH ROW BEGIN IF $predicate THEN IF GET_LOCK('$ready', 0) <> 1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'D3 ready lock failed'; END IF; IF GET_LOCK('$gate', 10) <> 1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'D3 gate timeout'; END IF; DO RELEASE_LOCK('$gate'); END IF; END");
        $triggerMade = true;
        $a = new D3WorkerProcess($entry, $name, $fixture[$deactivateFirst ? 'deactivate' : 'child'], $gate . '_a');
        $a->connected($server);
        $deadline = microtime(true) + 5;
        do {
            $owner = d3Scalar($server, 'SELECT IS_USED_LOCK(?)', [$ready]);
            if ((int) $owner === $a->connectionId) { break; }
            usleep(10_000);
        } while (microtime(true) < $deadline);
        d3Assert((int) $owner === $a->connectionId, 'First operation never reached guarded write');
        $b = new D3WorkerProcess($entry, $name, $fixture[$deactivateFirst ? 'child' : 'deactivate'], $gate . '_b');
        $b->connected($server);
        d3Assert($a->pid !== $b->pid && $a->pid !== getmypid() && $b->pid !== getmypid() && $a->connectionId !== $b->connectionId, 'Workers must be independent processes/connections');

        // Observe the actual InnoDB wait edge, not elapsed startup or a sleep.
        $sql = 'SELECT COUNT(*) FROM information_schema.INNODB_LOCK_WAITS w'
            . ' JOIN information_schema.INNODB_TRX waiting ON waiting.trx_id = w.requesting_trx_id'
            . ' JOIN information_schema.INNODB_TRX blocking ON blocking.trx_id = w.blocking_trx_id'
            . ' WHERE waiting.trx_mysql_thread_id = ? AND blocking.trx_mysql_thread_id = ?';
        $deadline = microtime(true) + 5;
        do {
            $waiting = (int) d3Scalar($server, $sql, [$b->connectionId, $a->connectionId]);
            if ($waiting > 0) { break; }
            // Leave time between reads for the InnoDB diagnostic snapshot.
            usleep(200_000);
        } while (microtime(true) < $deadline);
        if ($waiting === 0) {
            $diagnostic = $server->prepare('SELECT ID, STATE, INFO FROM information_schema.PROCESSLIST WHERE ID IN (?, ?)');
            $diagnostic->execute([$a->connectionId, $b->connectionId]);
            throw new RuntimeException('No actual B-to-A InnoDB lock contention observed: ' . json_encode($diagnostic->fetchAll(PDO::FETCH_ASSOC)));
        }
        $observed = microtime(true);
        do {
            usleep(200_000);
            d3Assert((int) d3Scalar($server, $sql, [$b->connectionId, $a->connectionId]) > 0, 'Wait edge disappeared before gate release');
        } while (microtime(true) - $observed < 0.3);
        $contention = microtime(true) - $observed;
        d3Assert((int) d3Scalar($server, 'SELECT RELEASE_LOCK(?)', [$gate]) === 1, 'Coordinator gate release');
        $resultA = $a->finish();
        $resultB = $b->finish();
        $expectedError = $deactivateFirst ? 'PARENT_CATALOG_INACTIVE' : $fixture['blocked_error'];
        d3Assert($resultA['result'] === 'ok', 'First operation failed: ' . json_encode($resultA));
        d3Assert($resultB['result'] === 'validation' && ($resultB['code'] ?? null) === $expectedError, 'Expected serialized domain rejection: ' . json_encode($resultB));
        d3Assert($resultB['elapsed_seconds'] >= $contention, 'Worker timing does not include observed contention');

        $parent = d3Scalar($pdo, 'SELECT status FROM `' . $fixture['parent_table'] . '` WHERE id = ?', [$fixture['parent_id']]);
        $children = $pdo->prepare('SELECT status FROM `' . $fixture['child_table'] . '` WHERE `' . $fixture['child_foreign_key'] . '` = ?');
        $children->execute([$fixture['parent_id']]);
        $statuses = $children->fetchAll(PDO::FETCH_COLUMN);
        d3Assert(!($parent === 'inactive' && in_array('active', $statuses, true)), 'Forbidden final state: inactive parent with active child');
        d3Assert($parent === ($deactivateFirst ? 'inactive' : 'active'), 'Unexpected committed parent state');
        $expectedStatuses = $deactivateFirst ? (isset($fixture['child_id']) ? ['inactive'] : []) : ['active'];
        d3Assert($statuses === $expectedStatuses, 'Unexpected child state/count: ' . json_encode($statuses));
        if (isset($fixture['verify'])) { $fixture['verify']($pdo); }
        echo strtoupper($unit) . " $order: PASS; observed_contention_seconds=" . number_format($contention, 3)
            . '; B_operation_seconds=' . number_format($resultB['elapsed_seconds'], 3) . '; domain_error=' . $expectedError . "\n";
    } finally {
        // Release coordination first, then kill any surviving worker sessions so
        // trigger/database cleanup cannot wait on a leaked transaction.
        d3Scalar($server, 'SELECT RELEASE_LOCK(?)', [$gate]);
        foreach ([$a, $b] as $worker) {
            if ($worker !== null) {
                if (isset($worker->connectionId) && (int) d3Scalar($server, 'SELECT COUNT(*) FROM information_schema.PROCESSLIST WHERE ID = ?', [$worker->connectionId]) > 0) {
                    try { $server->exec('KILL CONNECTION ' . $worker->connectionId); }
                    catch (PDOException $exception) { if ((int) ($exception->errorInfo[1] ?? 0) !== 1094) { throw $exception; } }
                }
                $worker->cleanup();
            }
        }
        if ($triggerMade) { $pdo->exec("DROP TRIGGER IF EXISTS `$trigger`"); }
        foreach ([$gate, $ready, $gate . '_a', $gate . '_b'] as $lock) { d3Assert((int) d3Scalar($server, 'SELECT IS_FREE_LOCK(?)', [$lock]) === 1, 'Named lock residue'); }
    }
}

function d3Run(string $unit, string $entry, callable $fixtureFactory): void
{
    $root = dirname(__DIR__, 2);
    $config = require $root . '/config/database.php';
    $connection = $config['connections']['mysql'];
    $name = 'directors_resale_platform_' . $unit . '_test_' . getmypid();
    d3Assert(preg_match('/^directors_resale_platform_d3[a-d]_test_[0-9]+$/D', $name) === 1 && $name !== $connection['database'], 'Unsafe database');
    $server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    d3Assert(str_contains((string) d3Scalar($server, 'SELECT VERSION()'), 'MariaDB'), 'Real MariaDB required');
    $created = false; $database = null;
    try {
        $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created = true;
        $config['connections']['mysql']['database'] = $name;
        $database = new DatabaseManager($config);
        $pdo = $database->connection();
        $files = glob($root . '/database/migrations/*.sql') ?: []; sort($files);
        foreach ($files as $file) { $pdo->exec(file_get_contents($file)); }
        $container = d3Container($database);
        foreach ([true, false] as $deactivateFirst) {
            $fixture = $fixtureFactory($container, $deactivateFirst ? 'DEACTIVATE' : 'CHILD');
            d3Race($server, $pdo, $entry, $name, $unit, $fixture, $deactivateFirst);
        }
    } finally {
        if ($database !== null && $database->connection()->inTransaction()) { $database->rollback(); }
        if ($created) { $server->exec("DROP DATABASE `$name`"); }
        d3Assert((int) d3Scalar($server, 'SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$name]) === 0, 'Test database residue');
    }
}
