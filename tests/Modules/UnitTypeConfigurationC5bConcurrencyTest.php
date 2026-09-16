<?php
declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\PropertyCatalogRepository;
use App\Modules\Property\Repositories\UnitTypeConfigurationRepository;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Modules\Property\Services\UnitTypeConfigurationService;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function c5bAssert(bool $condition, string $message): void { if (! $condition) { throw new RuntimeException($message); } }

/** @return array{result:string,code?:string,error?:string} */
function c5bWorker(string $root, string $databaseName, string $mode, int $unitTypeId, int $configurationId, int $measurementId): array
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
        if ($mode === 'activate') { $service->activate($unitTypeId, $configurationId); }
        else { $service->addMeasurementRule($unitTypeId, $configurationId, [
            'measurement_definition_id' => $measurementId, 'requirement' => 'REQUIRED', 'is_primary' => false,
        ]); }
        return ['result' => 'ok'];
    } catch (ValidationException $exception) {
        return ['result' => 'validation', 'code' => (string) array_key_first($exception->errors())];
    } catch (Throwable $exception) {
        return ['result' => 'error', 'error' => $exception::class . ': ' . $exception->getMessage()];
    }
}

/** @param array<int,resource> $pipes @return resource */
function c5bStart(string $databaseName, string $mode, int $unit, int $configuration, int $measurement, array &$pipes): mixed
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --c5b-worker '
        . escapeshellarg($databaseName) . ' ' . escapeshellarg($mode) . " $unit $configuration $measurement";
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));
    if (!is_resource($process)) { throw new RuntimeException('Worker launch failed.'); }
    stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);
    return $process;
}

/** @param resource $process @param array<int,resource> $pipes @return array{result:string,code?:string,error?:string} */
function c5bFinish(mixed $process, array $pipes): array
{
    $deadline = microtime(true) + 6;
    while (proc_get_status($process)['running']) {
        if (microtime(true) >= $deadline) { proc_terminate($process); throw new RuntimeException('Worker timeout.'); }
        usleep(20_000);
    }
    $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $exit = proc_close($process); $result = json_decode(trim($out), true);
    if ($exit !== 0 || !is_array($result)) { throw new RuntimeException('Worker failure: ' . trim($err . ' ' . $out)); }
    return $result;
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
if (($argv[1] ?? null) === '--c5b-worker') {
    $result = c5bWorker($root, (string) $argv[2], (string) $argv[3], (int) $argv[4], (int) $argv[5], (int) $argv[6]);
    echo json_encode($result, JSON_THROW_ON_ERROR); exit($result['result'] === 'error' ? 1 : 0);
}
$config = require $root . '/config/database.php'; $connection = $config['connections']['mysql'];
$name = 'directors_resale_platform_c5b_test_' . getmypid();
c5bAssert(preg_match('/^directors_resale_platform_c5b_test_[0-9]+$/D', $name) === 1 && $name !== $connection['database'], 'Unsafe database');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false; $database = null; $a = $b = null; $ap = $bp = [];
try {
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created = true;
    $config['connections']['mysql']['database'] = $name; $database = new DatabaseManager($config); $pdo = $database->connection();
    $files = glob($root . '/database/migrations/*.sql') ?: []; sort($files); foreach ($files as $file) { $pdo->exec(file_get_contents($file)); }
    $container = new Container(); (new AppServiceProvider($container, []))->register(); $container->instance(DatabaseConnectionInterface::class, $database);
    $catalog = $container->make(PropertyCatalogService::class); $service = $container->make(UnitTypeConfigurationService::class);
    $units = $container->make(PropertyCatalogRepository::class); $configs = $container->make(UnitTypeConfigurationRepository::class);
    $category = $catalog->createCategory(['code'=>'C5B','name_ar'=>'C5B','name_en'=>'C5B']);
    $unit = $catalog->createUnitType(['property_category_id'=>$category['id'],'code'=>'C5B','name_ar'=>'C5B','name_en'=>'C5B']);
    $measurement = $catalog->createMeasurementDefinition(['code'=>'C5B','name_ar'=>'C5B','name_en'=>'C5B','default_unit_code'=>'SQM']);
    $draft = $service->createBlankDraft($unit['id']);
    $before = $units->findUnitTypeById($unit['id'])['last_allocated_configuration_version'];
    $lock = 'bf014_c5b_activate_' . getmypid();
    $pdo->exec("CREATE TRIGGER c5b_pause BEFORE UPDATE ON unit_type_configuration_versions FOR EACH ROW
        BEGIN IF NEW.id = {$draft['id']} AND NEW.status = 'active' THEN DO GET_LOCK('$lock', 0); DO SLEEP(2); END IF; END");
    $a = c5bStart($name, 'activate', $unit['id'], $draft['id'], $measurement['id'], $ap);
    $deadline = microtime(true) + 3; $owner = null;
    do { $s=$server->prepare('SELECT IS_USED_LOCK(?)'); $s->execute([$lock]); $owner=$s->fetchColumn(); if ($owner !== null) break; usleep(20_000); } while(microtime(true)<$deadline);
    c5bAssert($owner !== null, 'Activation did not reach guarded status update');
    $startedB = microtime(true); $b = c5bStart($name, 'mutate', $unit['id'], $draft['id'], $measurement['id'], $bp);
    $ra = c5bFinish($a, $ap); $a = null; $rb = c5bFinish($b, $bp); $b = null; $wait = microtime(true)-$startedB;
    c5bAssert($ra['result'] === 'ok', 'Activation result');
    c5bAssert($rb['result'] === 'validation' && ($rb['code']??null) === 'CONFIGURATION_STRUCTURE_IMMUTABLE', 'Mutation result');
    c5bAssert($wait >= .5, 'Mutation did not contend');
    $final = $configs->findConfigurationById($draft['id']);
    c5bAssert($final['status'] === 'active', 'Final configuration status');
    c5bAssert($configs->listMeasurementRules($draft['id']) === [], 'Rejected mutation created a rule');
    c5bAssert(count($configs->listConfigurationsForUnitType($unit['id'], ['status'=>'active'])) === 1 && $configs->findDraftConfigurationForUnitType($unit['id']) === null, 'Final active/draft counts');
    c5bAssert($units->findUnitTypeById($unit['id'])['last_allocated_configuration_version'] === $before, 'Counter changed');
    echo "C5b real mutation vs activation: PASS; B_wait_seconds=" . number_format($wait,3) . "\n";
} finally {
    foreach ([[$a,$ap],[$b,$bp]] as [$p,$pipes]) { if ($p !== null) { proc_terminate($p); foreach($pipes as $pipe) fclose($pipe); proc_close($p); } }
    if ($database !== null && $database->connection()->inTransaction()) $database->rollback();
    if ($created) $server->exec("DROP DATABASE `$name`");
}
