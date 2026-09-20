<?php
declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Exceptions\ValidationException;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Modules\Property\Services\UnitTypeConfigurationService;
use App\Providers\AppServiceProvider;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
function provenanceCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function provenanceError(callable $call, string $code): void
{
    try { $call(); } catch (ValidationException $e) {
        provenanceCheck(array_key_first($e->errors()) === $code, 'Unexpected validation code');
        return;
    }
    throw new RuntimeException('Expected ' . $code);
}
$root = dirname(__DIR__, 2);
Dotenv\Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$connection = $config['connections']['mysql'];
$name = 'directors_resale_platform_seed_provenance_test_' . getmypid();
provenanceCheck(preg_match('/^directors_resale_platform_seed_provenance_test_[0-9]+$/D', $name) === 1 && $name !== $connection['database'], 'Unsafe database');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
$db = null;
try {
    provenanceCheck(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB required');
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $config['connections']['mysql']['database'] = $name;
    $db = new DatabaseManager($config);
    $pdo = $db->connection();
    $files = glob($root . '/database/migrations/*.sql');
    sort($files);
    foreach ($files as $file) { $pdo->exec(file_get_contents($file)); }
    $container = new Container();
    (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $db);
    $service = $container->make(UnitTypeConfigurationService::class);
    $catalog = $container->make(PropertyCatalogService::class);
    $category = $catalog->createCategory(['code'=>'TEST_SEED', 'name_ar'=>'Test', 'name_en'=>'Test']);
    $unit = $catalog->createUnitType(['code'=>'TEST_SEED', 'name_ar'=>'Test', 'name_en'=>'Test', 'property_category_id'=>$category['id']]);
    $id = $unit['id'];
    $measure = $catalog->createMeasurementDefinition(['code'=>'TEST_SEED', 'name_ar'=>'Test', 'name_en'=>'Test', 'default_unit_code'=>'SQM']);
    $default = $service->createBlankDraft($id);
    provenanceCheck($service->findConfiguration($default['id'])['provenance'] === 'SYSTEM_ADMIN', 'Default provenance');
    $service->activate($id, $default['id']);
    $snapshot = fn(): array => [
        $pdo->query('SELECT * FROM unit_type_configuration_versions ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query("SELECT last_allocated_configuration_version FROM unit_types WHERE id=$id")->fetchColumn(),
    ];
    foreach (['INVALID', '', 'system_seed'] as $invalid) {
        foreach ([false, true] as $clone) {
            $before = $snapshot();
            provenanceError(fn() => $clone ? $service->cloneActiveDraft($id, null, $invalid) : $service->createBlankDraft($id, null, $invalid), 'INVALID_CATALOG_PROVENANCE');
            provenanceCheck($snapshot() === $before && !$pdo->inTransaction(), 'Invalid provenance mutated rows or allocator');
        }
    }
    $seed = $service->createBlankDraft($id, null, 'SYSTEM_SEED');
    $stored = $service->findConfiguration($seed['id']);
    provenanceCheck($stored['provenance'] === 'SYSTEM_SEED' && $stored['status'] === 'draft', 'Seed blank persistence/status');
    provenanceError(fn() => $service->deleteDraft($id, $seed['id']), 'SYSTEM_SEED_DELETE_FORBIDDEN');
    $rule = $service->addMeasurementRule($id, $seed['id'], ['measurement_definition_id'=>$measure['id'], 'requirement'=>'REQUIRED', 'is_primary'=>true]);
    provenanceError(fn() => $service->updateMeasurementRule($id, $seed['id'], $rule['id'], ['requirement'=>'OPTIONAL']), 'PRIMARY_MEASUREMENT_MUST_BE_REQUIRED');
    $catalog->deactivateMeasurementDefinition($measure['id']);
    provenanceError(fn() => $service->activate($id, $seed['id']), 'MEASUREMENT_DEFINITION_INACTIVE');
    provenanceCheck($service->findDraft($id)['id'] === $seed['id'] && $service->findActive($id)['id'] === $default['id'], 'Failed activation changed lifecycle');
    $catalog->reactivateMeasurementDefinition($measure['id']);
    $service->activate($id, $seed['id']);
    provenanceCheck($service->findActive($id)['provenance'] === 'SYSTEM_SEED' && $service->findConfiguration($default['id'])['status'] === 'historical', 'Seed activation');
    provenanceError(fn() => $service->updateMeasurementRule($id, $seed['id'], $rule['id'], ['sort_order'=>9]), 'CONFIGURATION_STRUCTURE_IMMUTABLE');
    $clone = $service->cloneActiveDraft($id, null, 'SYSTEM_SEED');
    provenanceCheck($service->findConfiguration($clone['id'])['provenance'] === 'SYSTEM_SEED' && $clone['status'] === 'draft', 'Seed clone persistence/status');
    $clonedRules = $pdo->query('SELECT measurement_definition_id, requirement, is_primary FROM unit_type_measurement_rules WHERE configuration_version_id=' . $clone['id'])->fetchAll(PDO::FETCH_ASSOC);
    provenanceCheck(count($clonedRules) === 1 && (int)$clonedRules[0]['measurement_definition_id'] === $measure['id'] && $clonedRules[0]['requirement'] === 'REQUIRED' && (int)$clonedRules[0]['is_primary'] === 1, 'Clone rule semantics');
    $service->activate($id, $clone['id']);
    provenanceCheck($service->findActive($id)['id'] === $clone['id'] && $service->findConfiguration($seed['id'])['status'] === 'historical', 'Clone activation');
    echo "BF014 configuration seed provenance MariaDB acceptance: PASS\n";
} finally {
    if ($db !== null && $db->connection()->inTransaction()) { $db->rollback(); }
    if ($created) { $server->exec("DROP DATABASE `$name`"); }
}
