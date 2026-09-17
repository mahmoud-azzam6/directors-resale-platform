<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\UnitTypeConfigurationRepository;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Modules\Property\Services\UnitTypeConfigurationService;
use App\Modules\Property\Services\GeographicLocationService;
use App\Modules\Property\Services\DevelopmentCatalogService;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function e3Assert(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }
function e3Error(callable $operation, string $code): void
{
    try { $operation(); }
    catch (ValidationException $exception) { e3Assert(isset($exception->errors()[$code]), 'Expected ' . $code); return; }
    throw new RuntimeException('Expected ' . $code);
}
function e3Data(string $code, array $extra = []): array { return $extra + ['code' => $code, 'name_ar' => $code, 'name_en' => $code]; }

$root = dirname(__DIR__, 2); Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php'; $connection = $config['connections']['mysql'];
$name = 'directors_resale_platform_e3_test_' . getmypid();
e3Assert(preg_match('/^directors_resale_platform_e3_test_[0-9]+$/D', $name) === 1 && $name !== $connection['database'], 'Unsafe database');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
e3Assert(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB required');
$created = false; $database = null;
try {
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created = true;
    $config['connections']['mysql']['database'] = $name; $database = new DatabaseManager($config); $pdo = $database->connection();
    $files = glob($root . '/database/migrations/*.sql') ?: []; sort($files);
    foreach ($files as $file) { $pdo->exec(file_get_contents($file)); }
    $container = new Container(); (new AppServiceProvider($container, []))->register(); $container->instance(DatabaseConnectionInterface::class, $database);
    $catalog = $container->make(PropertyCatalogService::class);
    $configs = $container->make(UnitTypeConfigurationService::class);
    $rules = $container->make(UnitTypeConfigurationRepository::class);
    $geo = $container->make(GeographicLocationService::class);
    $dev = $container->make(DevelopmentCatalogService::class);
    $category = $catalog->createCategory(e3Data('CATEGORY'));
    $unit = $catalog->createUnitType(e3Data('UNIT', ['property_category_id' => $category['id']])); $u = $unit['id'];
    $measurement = $catalog->createMeasurementDefinition(e3Data('AREA', ['default_unit_code' => 'SQM']));
    $attribute = $catalog->createAttributeDefinition(e3Data('NOTE', ['data_type' => 'TEXT', 'text_max_length' => 100]));
    $v1 = $configs->createBlankDraft($u);
    $mr = $configs->addMeasurementRule($u, $v1['id'], ['measurement_definition_id' => $measurement['id'], 'requirement' => 'REQUIRED', 'is_primary' => true]);
    $ar = $configs->addAttributeRule($u, $v1['id'], ['attribute_definition_id' => $attribute['id'], 'requirement' => 'OPTIONAL']);
    $configs->activate($u, $v1['id']);

    // A: catalog lifecycle leaves configuration status, rules and history intact.
    $history = $configs->listConfigurations($u); $aggregate = $configs->aggregate($v1['id']);
    $catalog->deactivateUnitType($u);
    e3Assert($configs->listConfigurations($u) === $history && $configs->aggregate($v1['id']) === $aggregate, 'Unit deactivation changed configuration/history');
    $catalog->deactivateCategory($category['id']);
    e3Error(fn() => $catalog->reactivateUnitType($u), 'PARENT_CATALOG_INACTIVE');
    e3Assert($catalog->findUnitType($u)['status'] === 'inactive' && $configs->aggregate($v1['id']) === $aggregate, 'Failed unit reactivation changed state');
    $catalog->reactivateCategory($category['id']); $catalog->reactivateUnitType($u);
    e3Error(fn() => $catalog->deactivateCategory($category['id']), 'CATALOG_ITEM_REFERENCED');
    $v2 = $configs->cloneActiveDraft($u); $configs->activate($u, $v2['id']);
    $draft = $configs->createBlankDraft($u); $configs->deleteDraft($u, $draft['id']);
    e3Assert($catalog->findCategory($category['id'])['status'] === 'active' && $catalog->findUnitType($u)['status'] === 'active', 'Configuration lifecycle changed catalog status');
    e3Assert($configs->findConfiguration($v1['id'])['status'] === 'historical' && $configs->findActive($u)['id'] === $v2['id'], 'Version replacement');
    echo "Edge A: PASS\n";

    // B: aggregate reads resolve current canonical metadata, not version snapshots.
    $historicalRecord = $configs->findConfiguration($v1['id']);
    $catalog->updateMeasurementDefinition($measurement['id'], ['name_en' => 'Updated area label', 'sort_order' => 7]);
    $catalog->updateAttributeDefinition($attribute['id'], ['name_en' => 'Updated note label', 'sort_order' => 8]);
    foreach ([$v1, $v2] as $version) {
        $read = $configs->aggregate($version['id']);
        e3Assert($read['measurement_rules'][0]['definition']['id'] === $measurement['id'] && $read['measurement_rules'][0]['definition']['name_en'] === 'Updated area label', 'Shared Measurement identity/metadata');
        e3Assert($read['attribute_rules'][0]['definition']['id'] === $attribute['id'] && $read['attribute_rules'][0]['definition']['name_en'] === 'Updated note label', 'Shared Attribute identity/metadata');
    }
    e3Assert($rules->listMeasurementRules($v1['id']) === [$mr] && $rules->listAttributeRules($v1['id']) === [$ar] && $configs->findConfiguration($v1['id']) === $historicalRecord, 'Catalog edit rewrote historical structure');
    e3Assert((int) $pdo->query('SELECT COUNT(*) FROM measurement_definitions')->fetchColumn() === 1 && (int) $pdo->query('SELECT COUNT(*) FROM attribute_definitions')->fetchColumn() === 1, 'Definitions duplicated per version');
    e3Error(fn() => $configs->updateMeasurementRule($u, $v1['id'], $mr['id'], ['sort_order' => 99]), 'CONFIGURATION_STRUCTURE_IMMUTABLE');
    e3Assert($rules->listMeasurementRules($v1['id']) === [$mr], 'Historical mutation persisted');
    echo "Edge B: PASS\n";

    // C: assignment eligibility is distinct from continuing lifecycle coupling.
    $location = $geo->createLocation(e3Data('COUNTRY', ['location_type' => 'COUNTRY']));
    $developer = $dev->createDeveloper(e3Data('DEVELOPER'));
    $project = $dev->createProject(e3Data('PROJECT', ['developer_id' => $developer['id'], 'geographic_location_id' => $location['id']]));
    $geo->deactivateLocation($location['id']);
    e3Assert($dev->findProject($project['id']) === $project, 'Geography changed Project');
    $inactiveGeo = $geo->findLocation($location['id']);
    $dev->deactivateProject($project['id']); $dev->reactivateProject($project['id']);
    e3Assert($geo->findLocation($location['id']) === $inactiveGeo && $dev->findProject($project['id'])['geographic_location_id'] === $location['id'], 'Development changed Geography/reference');
    e3Error(fn() => $dev->createProject(e3Data('REJECTED', ['geographic_location_id' => $location['id']])), 'PARENT_CATALOG_INACTIVE');
    e3Assert(count($dev->listProjects()) === 1, 'Rejected assignment created Project');
    $projectBeforeGeo = $dev->findProject($project['id']);
    $geo->reactivateLocation($location['id']); $geo->deactivateLocation($location['id']);
    e3Assert($dev->findProject($project['id']) === $projectBeforeGeo, 'Geography lifecycle changed Project status/data');
    echo "Edge C: PASS\n";

    // D: cross-service references block deletion; deleting references never cascades.
    e3Error(fn() => $geo->deleteLocation($location['id']), 'CATALOG_ITEM_REFERENCED');
    e3Error(fn() => $catalog->deleteMeasurementDefinition($measurement['id']), 'CATALOG_ITEM_REFERENCED');
    e3Error(fn() => $catalog->deleteAttributeDefinition($attribute['id']), 'CATALOG_ITEM_REFERENCED');
    $geoBeforeDelete = $geo->findLocation($location['id']);
    e3Assert($dev->deleteProject($project['id']) && $dev->findProject($project['id']) === null, 'Eligible Project delete');
    e3Assert($geo->findLocation($location['id']) === $geoBeforeDelete && $dev->findDeveloper($developer['id']) === $developer, 'Project deletion cascaded');
    $draftOnly = $catalog->createMeasurementDefinition(e3Data('DRAFT-ONLY', ['default_unit_code' => 'SQM']));
    $draft = $configs->createBlankDraft($u);
    $configs->addMeasurementRule($u, $draft['id'], ['measurement_definition_id' => $draftOnly['id'], 'requirement' => 'OPTIONAL']);
    e3Error(fn() => $catalog->deleteMeasurementDefinition($draftOnly['id']), 'CATALOG_ITEM_REFERENCED');
    $configs->deleteDraft($u, $draft['id']);
    e3Assert($configs->aggregate($draft['id']) === null && $rules->listMeasurementRules($draft['id']) === [] && $catalog->findMeasurementDefinition($draftOnly['id']) === $draftOnly, 'Draft deletion cascaded into canonical Definition');
    echo "Edge D: PASS\n";

    // E: small, explicit family snapshots include every row in the affected scope.
    $isolatedProject = $dev->createProject(e3Data('ISOLATION', ['developer_id' => $developer['id']]));
    $dev->createProjectPhase($isolatedProject['id'], e3Data('ISOLATION-PHASE'));
    $geoDevelopmentBefore = [
        $pdo->query('SELECT * FROM geographic_locations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query('SELECT * FROM developers ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query('SELECT * FROM projects ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query('SELECT * FROM project_phases ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    ];
    e3Error(fn() => $configs->removeAttributeRule($u, $v1['id'], $ar['id']), 'CONFIGURATION_STRUCTURE_IMMUTABLE');
    e3Assert($geoDevelopmentBefore === [
        $pdo->query('SELECT * FROM geographic_locations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query('SELECT * FROM developers ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query('SELECT * FROM projects ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query('SELECT * FROM project_phases ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    ], 'Configuration failure changed Geography/Development');
    $catalogBefore = [$catalog->findCategory($category['id']), $catalog->findUnitType($u), $catalog->listMeasurementDefinitions(), $catalog->listAttributeDefinitions(), $configs->listConfigurations($u), $configs->aggregate($v1['id']), $configs->aggregate($v2['id'])];
    e3Error(fn() => $dev->createProject(e3Data('INVALID', ['geographic_location_id' => $location['id']])), 'PARENT_CATALOG_INACTIVE');
    e3Assert($catalogBefore === [$catalog->findCategory($category['id']), $catalog->findUnitType($u), $catalog->listMeasurementDefinitions(), $catalog->listAttributeDefinitions(), $configs->listConfigurations($u), $configs->aggregate($v1['id']), $configs->aggregate($v2['id'])], 'Development failure changed Catalog/Configuration');
    e3Assert(!$pdo->inTransaction(), 'Failure left an open transaction');
    echo "Edge E: PASS\nE3 cross-service MariaDB edge acceptance: PASS\n";
} finally {
    if ($database !== null && $database->connection()->inTransaction()) { $database->rollback(); }
    if ($created) { $server->exec("DROP DATABASE `$name`"); }
    $remaining = $server->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?'); $remaining->execute([$name]);
    e3Assert((int) $remaining->fetchColumn() === 0, 'Test database residue');
}
