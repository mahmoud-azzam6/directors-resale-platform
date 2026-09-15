<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\AttributeDefinitionRepository;
use App\Modules\Property\Repositories\MeasurementDefinitionRepository;
use App\Modules\Property\Repositories\PropertyCatalogRepository;
use App\Modules\Property\Repositories\UnitTypeConfigurationRepository;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function bf014ServiceAssert(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
}

function bf014ServiceCode(callable $operation, string $code): void
{
    try { $operation(); }
    catch (ValidationException $exception) {
        bf014ServiceAssert(array_key_exists($code, $exception->errors()), "Expected {$code}");
        return;
    }
    throw new RuntimeException("Expected ValidationException {$code}");
}

/** @return array{result:string,code?:string,error?:string} */
function bf014ServiceWorker(string $root, string $databaseName, int $definitionId, int $optionId): array
{
    $config = require $root . '/config/database.php';
    $config['connections']['mysql']['database'] = $databaseName;
    $database = new DatabaseManager($config);
    $database->connection()->exec('SET SESSION innodb_lock_wait_timeout = 5');
    $container = new Container();
    (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $database);
    $service = $container->make(PropertyCatalogService::class);
    try {
        $service->deactivateAttributeOption($definitionId, $optionId);
        return ['result' => 'ok'];
    } catch (ValidationException $exception) {
        return ['result' => 'validation', 'code' => (string) array_key_first($exception->errors())];
    } catch (Throwable $exception) {
        return ['result' => 'error', 'error' => $exception::class . ': ' . $exception->getMessage()];
    }
}

/** @return resource */
function bf014ServiceStartWorker(string $databaseName, int $definitionId, int $optionId, array &$pipes): mixed
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --bf014-service-worker '
        . escapeshellarg($databaseName) . ' ' . $definitionId . ' ' . $optionId;
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));
    if (! is_resource($process)) { throw new RuntimeException('Unable to start concurrency worker.'); }
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    return $process;
}

/** @param resource $process @param array<int,resource> $pipes @return array{result:string,code?:string,error?:string} */
function bf014ServiceFinishWorker(mixed $process, array $pipes, float $timeoutSeconds): array
{
    $deadline = microtime(true) + $timeoutSeconds;
    while (($status = proc_get_status($process))['running']) {
        if (microtime(true) >= $deadline) {
            proc_terminate($process);
            throw new RuntimeException('Concurrency worker timed out.');
        }
        usleep(20_000);
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    $result = json_decode(trim($stdout), true);
    if ($exitCode !== 0 || ! is_array($result)) {
        throw new RuntimeException('Concurrency worker failed: ' . trim($stderr . ' ' . $stdout));
    }
    return $result;
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$worker = $argv[1] ?? null;
if ($worker === '--bf014-service-worker') {
    $result = bf014ServiceWorker($root, (string) ($argv[2] ?? ''), (int) ($argv[3] ?? 0), (int) ($argv[4] ?? 0));
    echo json_encode($result, JSON_THROW_ON_ERROR);
    exit($result['result'] === 'error' ? 1 : 0);
}
$config = require $root . '/config/database.php';
$connection = $config['connections']['mysql'];
$developmentDatabase = (string) $connection['database'];
$databaseName = 'directors_resale_platform_bf014_service_test_' . getmypid();
$safeName = static fn (string $name): bool => preg_match('/^directors_resale_platform_bf014_service_test_[0-9]+$/D', $name) === 1;
bf014ServiceAssert($safeName($databaseName) && $databaseName !== $developmentDatabase, 'Unsafe test database name');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), (string) $connection['username'], (string) $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
$database = null;
$cleanup = static function () use ($server, $databaseName, $developmentDatabase, $safeName, &$created, &$database): void {
    if (! $created) { return; }
    if ($database !== null && $database->connection()->inTransaction()) { $database->rollback(); }
    bf014ServiceAssert($safeName($databaseName) && $databaseName !== $developmentDatabase, 'Unsafe cleanup refused');
    $server->exec("DROP DATABASE `{$databaseName}`");
    $created = false;
};
register_shutdown_function($cleanup);

try {
    bf014ServiceAssert(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB required');
    $server->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $config['connections']['mysql']['database'] = $databaseName;
    $database = new DatabaseManager($config);
    $pdo = $database->connection();
    $pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    $migrations = glob($root . '/database/migrations/*.sql') ?: [];
    sort($migrations, SORT_STRING);
    foreach ($migrations as $migration) { $pdo->exec(file_get_contents($migration)); }

    $container = new Container();
    (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $database);
    $service = $container->make(PropertyCatalogService::class);
    $catalog = $container->make(PropertyCatalogRepository::class);
    $measurements = $container->make(MeasurementDefinitionRepository::class);
    $attributes = $container->make(AttributeDefinitionRepository::class);
    $configurations = $container->make(UnitTypeConfigurationRepository::class);
    bf014ServiceAssert($service instanceof PropertyCatalogService, 'Container service binding');

    $category = $service->createCategory(['code' => ' apartment ', 'name_ar' => 'شقق', 'name_en' => 'Apartments']);
    bf014ServiceAssert($category['code'] === 'APARTMENT' && $category['status'] === 'active', 'normalized active category');
    bf014ServiceCode(fn () => $service->createCategory(['code' => 'APARTMENT', 'name_ar' => 'x', 'name_en' => 'x']), 'CATALOG_CODE_ALREADY_EXISTS');
    bf014ServiceCode(fn () => $service->updateCategory($category['id'], ['code' => 'NEW']), 'CATALOG_IDENTITY_IMMUTABLE');
    $unit = $service->createUnitType(['property_category_id' => $category['id'], 'code' => 'flat', 'name_ar' => 'وحدة', 'name_en' => 'Flat']);
    bf014ServiceAssert($unit['last_allocated_configuration_version'] === 0, 'Service leaves allocation state unchanged');
    bf014ServiceAssert(count($service->listCategories()) === 1 && count($service->listUnitTypes(['property_category_id' => $category['id']])) === 1, 'Catalog reads/lists');
    bf014ServiceCode(fn () => $service->deactivateCategory($category['id']), 'CATALOG_ITEM_REFERENCED');
    bf014ServiceAssert($service->findCategory($category['id'])['status'] === 'active', 'failed transition rolls back');
    $service->deactivateUnitType($unit['id']);
    $service->deactivateCategory($category['id']);
    bf014ServiceCode(fn () => $service->reactivateUnitType($unit['id']), 'PARENT_CATALOG_INACTIVE');
    $service->reactivateCategory($category['id']);
    $service->reactivateUnitType($unit['id']);
    $otherCategory = $service->createCategory(['code' => 'VILLA', 'name_ar' => 'فلل', 'name_en' => 'Villas']);
    $unit = $service->updateUnitType($unit['id'], ['property_category_id' => $otherCategory['id']]);
    bf014ServiceAssert($unit['property_category_id'] === $otherCategory['id'], 'Unit Type reparented to active Category');

    $measurement = $service->createMeasurementDefinition(['code' => 'area', 'name_ar' => 'مساحة', 'name_en' => 'Area', 'default_unit_code' => 'SQM']);
    $enum = $service->createAttributeDefinition(['code' => 'view', 'name_ar' => 'إطلالة', 'name_en' => 'View', 'data_type' => 'ENUM']);
    $text = $service->createAttributeDefinition(['code' => 'note', 'name_ar' => 'ملاحظة', 'name_en' => 'Note', 'data_type' => 'TEXT', 'text_max_length' => 100]);
    bf014ServiceCode(fn () => $service->updateMeasurementDefinition($measurement['id'], ['default_unit_code' => 'FT']), 'CATALOG_IDENTITY_IMMUTABLE');
    bf014ServiceCode(fn () => $service->updateAttributeDefinition($text['id'], ['data_type' => 'ENUM']), 'CATALOG_IDENTITY_IMMUTABLE');
    bf014ServiceCode(fn () => $service->createAttributeOption($text['id'], ['code' => 'BAD', 'name_ar' => 'x', 'name_en' => 'x']), 'ATTRIBUTE_OPTIONS_REQUIRE_ENUM');
    $one = $service->createAttributeOption($enum['id'], ['code' => 'city', 'name_ar' => 'مدينة', 'name_en' => 'City']);
    $two = $service->createAttributeOption($enum['id'], ['code' => 'sea', 'name_ar' => 'بحر', 'name_en' => 'Sea']);
    bf014ServiceAssert(count($service->listAttributeOptions($enum['id'])) === 2, 'Scoped option list');

    $configuration = $configurations->createConfigurationVersion(['ulid' => str_repeat('A', 26), 'unit_type_id' => $unit['id'], 'version_number' => 1, 'status' => 'active', 'provenance' => 'SYSTEM_ADMIN']);
    $configurations->createMeasurementRule($configuration['id'], ['measurement_definition_id' => $measurement['id'], 'requirement' => 'REQUIRED', 'is_primary' => 1]);
    $configurations->createAttributeRule($configuration['id'], ['attribute_definition_id' => $enum['id'], 'requirement' => 'REQUIRED']);
    $configurationBeforeUnitDeactivation = $configurations->findConfigurationAggregateById($configuration['id']);
    $allocationBeforeDeactivation = $service->findUnitType($unit['id'])['last_allocated_configuration_version'];
    $service->deactivateUnitType($unit['id']);
    bf014ServiceAssert($service->findUnitType($unit['id'])['status'] === 'inactive', 'Unit Type deactivated with active Configuration');
    $configurationAfterUnitDeactivation = $configurations->findConfigurationById($configuration['id']);
    bf014ServiceAssert($configurationAfterUnitDeactivation['status'] === 'active' && $configurationAfterUnitDeactivation['version_number'] === 1, 'Active Configuration preserved by Unit Type deactivation');
    $configurationRulesAfterUnitDeactivation = $configurations->findConfigurationAggregateById($configuration['id']);
    bf014ServiceAssert($configurationRulesAfterUnitDeactivation['measurement_rules'] === $configurationBeforeUnitDeactivation['measurement_rules'] && $configurationRulesAfterUnitDeactivation['attribute_rules'] === $configurationBeforeUnitDeactivation['attribute_rules'], 'Unit Type deactivation does not mutate active Configuration rules');
    bf014ServiceAssert($service->findUnitType($unit['id'])['last_allocated_configuration_version'] === $allocationBeforeDeactivation, 'Unit Type deactivation preserves allocation counter');
    $service->reactivateUnitType($unit['id']);
    bf014ServiceCode(fn () => $service->deactivateMeasurementDefinition($measurement['id']), 'CATALOG_ITEM_REFERENCED');
    bf014ServiceCode(fn () => $service->deactivateAttributeDefinition($enum['id']), 'CATALOG_ITEM_REFERENCED');
    $service->deactivateAttributeOption($enum['id'], $one['id']);
    bf014ServiceCode(fn () => $service->deactivateAttributeOption($enum['id'], $two['id']), 'ENUM_ACTIVE_OPTIONS_REQUIRED');
    bf014ServiceCode(fn () => $service->deleteAttributeDefinition($enum['id']), 'CATALOG_ITEM_REFERENCED');
    bf014ServiceCode(fn () => $service->deleteMeasurementDefinition($measurement['id']), 'CATALOG_ITEM_REFERENCED');

    $concurrencyUnit = $service->createUnitType(['property_category_id' => $otherCategory['id'], 'code' => 'CONCURRENT', 'name_ar' => 'متزامن', 'name_en' => 'Concurrent']);
    $concurrencyDefinition = $service->createAttributeDefinition(['code' => 'CONCURRENT_ENUM', 'name_ar' => 'تزامن', 'name_en' => 'Concurrent enum', 'data_type' => 'ENUM']);
    $concurrencyOne = $service->createAttributeOption($concurrencyDefinition['id'], ['code' => 'ONE', 'name_ar' => 'واحد', 'name_en' => 'One']);
    $concurrencyTwo = $service->createAttributeOption($concurrencyDefinition['id'], ['code' => 'TWO', 'name_ar' => 'اثنان', 'name_en' => 'Two']);
    $concurrencyConfiguration = $configurations->createConfigurationVersion(['ulid' => str_repeat('C', 26), 'unit_type_id' => $concurrencyUnit['id'], 'version_number' => 1, 'status' => 'active', 'provenance' => 'SYSTEM_ADMIN']);
    $configurations->createAttributeRule($concurrencyConfiguration['id'], ['attribute_definition_id' => $concurrencyDefinition['id'], 'requirement' => 'REQUIRED']);
    $lockName = 'bf014_service_enum_' . getmypid();
    $pdo->exec("CREATE TRIGGER bf014_service_enum_pause BEFORE UPDATE ON attribute_options FOR EACH ROW\n"
        . "BEGIN\nIF OLD.status = 'active' AND NEW.status = 'inactive' AND OLD.attribute_definition_id = {$concurrencyDefinition['id']} THEN\nDO GET_LOCK('{$lockName}', 0);\nDO SLEEP(2);\nEND IF;\nEND");
    $aPipes = $bPipes = [];
    $workerA = $workerB = null;
    try {
        $startedA = microtime(true);
        $workerA = bf014ServiceStartWorker($databaseName, $concurrencyDefinition['id'], $concurrencyOne['id'], $aPipes);
        $deadline = microtime(true) + 3.0;
        do {
            $observer = $server->prepare('SELECT IS_USED_LOCK(?)');
            $observer->execute([$lockName]);
            $lockOwner = $observer->fetchColumn();
            if ($lockOwner !== null) { break; }
            usleep(20_000);
        } while (microtime(true) < $deadline);
        bf014ServiceAssert($lockOwner !== null, 'Service A reached locked option update');
        $startedB = microtime(true);
        $workerB = bf014ServiceStartWorker($databaseName, $concurrencyDefinition['id'], $concurrencyTwo['id'], $bPipes);
        $resultA = bf014ServiceFinishWorker($workerA, $aPipes, 6.0);
        $workerA = null;
        $resultB = bf014ServiceFinishWorker($workerB, $bPipes, 6.0);
        $workerB = null;
        bf014ServiceAssert($resultA['result'] === 'ok', 'Service A deactivated first option');
        bf014ServiceAssert(($resultB['result'] ?? null) === 'validation' && ($resultB['code'] ?? null) === 'ENUM_ACTIVE_OPTIONS_REQUIRED', 'Service B re-evaluated after serialization');
        bf014ServiceAssert(microtime(true) - $startedB >= 0.5, 'Service B actually waited for Service A serialization');
        bf014ServiceAssert(count($attributes->listAttributeOptions($concurrencyDefinition['id'], ['status' => 'active'])) === 1, 'Concurrent transition leaves one active ENUM option');
    } finally {
        if ($workerA !== null) { proc_terminate($workerA); foreach ($aPipes as $pipe) { fclose($pipe); } proc_close($workerA); }
        if ($workerB !== null) { proc_terminate($workerB); foreach ($bPipes as $pipe) { fclose($pipe); } proc_close($workerB); }
        $pdo->exec('DROP TRIGGER IF EXISTS bf014_service_enum_pause');
    }

    $seed = $catalog->createCategory(['ulid' => str_repeat('B', 26), 'code' => 'SEED', 'name_ar' => 'بذرة', 'name_en' => 'Seed', 'status' => 'active', 'provenance' => 'SYSTEM_SEED', 'sort_order' => 0]);
    bf014ServiceCode(fn () => $service->deleteCategory($seed['id']), 'SYSTEM_SEED_DELETE_FORBIDDEN');
    $deletable = $service->createCategory(['code' => 'DELETE_ME', 'name_ar' => 'حذف', 'name_en' => 'Delete']);
    bf014ServiceAssert($service->deleteCategory($deletable['id']), 'Unreferenced SYSTEM_ADMIN delete');
    bf014ServiceCode(fn () => $service->deleteCategory($otherCategory['id']), 'CATALOG_ITEM_REFERENCED');
    bf014ServiceAssert($service->findUnitType($unit['id'])['last_allocated_configuration_version'] === 0, 'Catalog operations do not allocate versions');
    echo "PropertyCatalogService MariaDB acceptance: PASS\n";
} finally {
    $cleanup();
}
