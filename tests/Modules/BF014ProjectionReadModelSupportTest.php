<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Modules\Property\Repositories\AttributeDefinitionRepository;
use App\Modules\Property\Repositories\DevelopmentCatalogRepository;
use App\Modules\Property\Repositories\GeographicLocationRepository;
use App\Modules\Property\Repositories\MeasurementDefinitionRepository;
use App\Modules\Property\Repositories\PropertyCatalogRepository;
use App\Modules\Property\Repositories\UnitTypeConfigurationRepository;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function bf014ProjectionAssert(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function bf014ProjectionEqual(mixed $actual, mixed $expected, string $message): void
{
    bf014ProjectionAssert($actual === $expected, $message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}

function bf014ProjectionIds(array $rows): array
{
    return array_column($rows, 'id');
}

function bf014ProjectionExecutions(PDO $pdo): int
{
    $emulated = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    try {
        return (int) $pdo->query("SHOW SESSION STATUS LIKE 'Com_stmt_execute'")->fetch()['Value'];
    } finally {
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulated);
    }
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$connection = $config['connections']['mysql'];
$developmentDatabase = (string) $connection['database'];
$databaseName = 'directors_resale_platform_bf014_projection_test_' . getmypid();
$safeName = static fn (string $name): bool => preg_match('/^directors_resale_platform_bf014_projection_test_[0-9]+$/D', $name) === 1;
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']),
    (string) $connection['username'], (string) $connection['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
$created = false;
$database = null;
$cleanup = static function () use ($server, $databaseName, $developmentDatabase, $safeName, &$created, &$database): void {
    if (! $created) {
        return;
    }
    if ($database !== null && $database->connection()->inTransaction()) {
        $database->rollback();
    }
    bf014ProjectionAssert($safeName($databaseName) && $databaseName !== $developmentDatabase, 'Unsafe cleanup refused');
    $server->exec("DROP DATABASE `{$databaseName}`");
    $created = false;
};
register_shutdown_function($cleanup);

try {
    bf014ProjectionAssert(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB required');
    bf014ProjectionAssert($safeName($databaseName) && $databaseName !== $developmentDatabase, 'Unsafe test database name');
    $server->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $config['default'] = 'mysql';
    $config['connections']['mysql']['database'] = $databaseName;
    $database = new DatabaseManager($config);
    $pdo = $database->connection();
    foreach (glob($root . '/database/migrations/*.sql') ?: [] as $migration) {
        $pdo->exec(file_get_contents($migration));
    }

    $container = new Container();
    (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $database);
    $catalog = $container->make(PropertyCatalogRepository::class);
    $measurements = $container->make(MeasurementDefinitionRepository::class);
    $attributes = $container->make(AttributeDefinitionRepository::class);
    $configurations = $container->make(UnitTypeConfigurationRepository::class);
    $geography = $container->make(GeographicLocationRepository::class);
    $development = $container->make(DevelopmentCatalogRepository::class);
    $ulids = $container->make(UlidGeneratorInterface::class);
    $data = static fn (string $code, array $extra = []): array => array_replace([
        'ulid' => $ulids->generate(), 'code' => $code, 'name_ar' => 'Arabic ' . $code,
        'name_en' => 'English ' . $code, 'status' => 'active', 'provenance' => 'SYSTEM_ADMIN', 'sort_order' => 0,
    ], $extra);

    $category = $catalog->createCategory($data('CATEGORY', ['sort_order' => 20]));
    $inactiveCategory = $catalog->createCategory($data('CATEGORY_INACTIVE', ['status' => 'inactive', 'sort_order' => 10]));
    $unit = $catalog->createUnitType($data('UNIT', ['property_category_id' => $category['id'], 'sort_order' => 20]));
    $inactiveUnit = $catalog->createUnitType($data('UNIT_INACTIVE', ['property_category_id' => $category['id'], 'status' => 'inactive', 'sort_order' => 10]));
    $country = $geography->createGeographicLocation($data('COUNTRY', ['location_type' => 'COUNTRY', 'parent_id' => null, 'sort_order' => 20]));
    $inactiveCountry = $geography->createGeographicLocation($data('COUNTRY_INACTIVE', ['location_type' => 'COUNTRY', 'parent_id' => null, 'status' => 'inactive', 'sort_order' => 10]));
    $geography->createGeographicLocation($data('CHILD', ['location_type' => 'GOVERNORATE', 'parent_id' => $country['id']]));
    $developer = $development->createDeveloper($data('DEVELOPER', ['sort_order' => 20]));
    $development->createDeveloper($data('DEVELOPER_INACTIVE', ['status' => 'inactive', 'sort_order' => 10]));

    $measurementA = $measurements->createMeasurementDefinition($data('MEASUREMENT_A', ['default_unit_code' => 'SQM', 'sort_order' => 20]));
    $measurementB = $measurements->createMeasurementDefinition($data('MEASUREMENT_B', ['default_unit_code' => 'SQM', 'sort_order' => 10]));
    $enumA = $attributes->createAttributeDefinition($data('ENUM_A', ['data_type' => 'ENUM', 'sort_order' => 20]));
    $enumB = $attributes->createAttributeDefinition($data('ENUM_B', ['data_type' => 'ENUM', 'sort_order' => 10]));
    $unrelated = $attributes->createAttributeDefinition($data('ENUM_UNRELATED', ['data_type' => 'ENUM']));
    $activeA = $attributes->createAttributeOption($enumA['id'], $data('ACTIVE_A', ['sort_order' => 20]));
    $activeB = $attributes->createAttributeOption($enumB['id'], $data('ACTIVE_B', ['sort_order' => 10]));
    $attributes->createAttributeOption($enumA['id'], $data('INACTIVE_A', ['status' => 'inactive', 'sort_order' => 10]));
    $attributes->createAttributeOption($unrelated['id'], $data('UNRELATED'));

    $activeConfiguration = $configurations->createConfigurationVersion([
        'ulid' => $ulids->generate(), 'unit_type_id' => $unit['id'], 'version_number' => 1,
        'status' => 'active', 'provenance' => 'SYSTEM_ADMIN',
    ]);
    $configurations->createConfigurationVersion([
        'ulid' => $ulids->generate(), 'unit_type_id' => $unit['id'], 'version_number' => 2,
        'status' => 'historical', 'provenance' => 'SYSTEM_ADMIN',
    ]);
    $measurementRuleB = $configurations->createMeasurementRule($activeConfiguration['id'], [
        'measurement_definition_id' => $measurementB['id'], 'requirement' => 'OPTIONAL', 'is_primary' => false, 'sort_order' => 10,
    ]);
    $measurementRuleA = $configurations->createMeasurementRule($activeConfiguration['id'], [
        'measurement_definition_id' => $measurementA['id'], 'requirement' => 'REQUIRED', 'is_primary' => true, 'sort_order' => 20,
    ]);
    $attributeRuleB = $configurations->createAttributeRule($activeConfiguration['id'], [
        'attribute_definition_id' => $enumB['id'], 'requirement' => 'OPTIONAL', 'sort_order' => 10,
    ]);
    $attributeRuleA = $configurations->createAttributeRule($activeConfiguration['id'], [
        'attribute_definition_id' => $enumA['id'], 'requirement' => 'REQUIRED', 'sort_order' => 20,
    ]);

    bf014ProjectionEqual($catalog->findUnitTypeById($unit['id']), $unit, 'Unit Type by ID');
    bf014ProjectionEqual($catalog->findCategoryById($unit['property_category_id']), $category, 'Unit Type category');
    bf014ProjectionEqual($configurations->findActiveConfigurationForUnitType($unit['id']), $activeConfiguration, 'Current active Configuration');
    bf014ProjectionEqual(bf014ProjectionIds($configurations->listMeasurementRules($activeConfiguration['id'])), [$measurementRuleB['id'], $measurementRuleA['id']], 'Complete ordered Measurement Rules');
    bf014ProjectionEqual(bf014ProjectionIds($configurations->listAttributeRules($activeConfiguration['id'])), [$attributeRuleB['id'], $attributeRuleA['id']], 'Complete ordered Attribute Rules');
    bf014ProjectionEqual(bf014ProjectionIds($measurements->findMeasurementDefinitionsByIds([$measurementA['id'], $measurementB['id'], $measurementA['id'], 999999])), [$measurementA['id'], $measurementB['id']], 'Measurement Definition batch mapping');
    bf014ProjectionEqual(bf014ProjectionIds($attributes->findAttributeDefinitionsByIds([$enumA['id'], $enumB['id'], $enumA['id'], 999999])), [$enumA['id'], $enumB['id']], 'Attribute Definition batch deterministic ordering');
    bf014ProjectionEqual(bf014ProjectionIds($attributes->listActiveAttributeOptionsForDefinitions([$enumB['id'], $enumA['id'], $enumA['id']])), [$activeA['id'], $activeB['id']], 'Active Options batch scope and order');
    bf014ProjectionEqual($attributes->listActiveAttributeOptionsForDefinitions([]), [], 'Empty active Options batch');
    bf014ProjectionEqual($measurements->findMeasurementDefinitionsByIds([]), [], 'Empty Measurement batch');
    bf014ProjectionEqual($attributes->findAttributeDefinitionsByIds([]), [], 'Empty Attribute batch');
    bf014ProjectionEqual(bf014ProjectionIds($catalog->listCategories(['status' => 'active'])), [$category['id']], 'Active Categories');
    bf014ProjectionEqual(bf014ProjectionIds($catalog->listUnitTypes(['status' => 'active'])), [$unit['id']], 'Active Unit Types');
    bf014ProjectionEqual(bf014ProjectionIds($geography->listGeographicLocations(['status' => 'active', 'location_type' => 'COUNTRY', 'parent_id' => null])), [$country['id']], 'Active root Countries');
    bf014ProjectionEqual(bf014ProjectionIds($development->listDevelopers(['status' => 'active'])), [$developer['id']], 'Active Developers');
    bf014ProjectionAssert($inactiveCategory['id'] > 0 && $inactiveUnit['id'] > 0 && $inactiveCountry['id'] > 0, 'Inactive records retained outside active lists');

    $before = bf014ProjectionExecutions($pdo);
    $attributes->listActiveAttributeOptionsForDefinitions([$enumA['id'], $enumB['id']]);
    bf014ProjectionEqual(bf014ProjectionExecutions($pdo) - $before, 1, 'One active Options batch query');
    $before = bf014ProjectionExecutions($pdo);
    $attributes->listActiveAttributeOptionsForDefinitions([]);
    bf014ProjectionEqual(bf014ProjectionExecutions($pdo), $before, 'Empty active Options batch executes no SQL');
    echo "BF014.6A projection read-model support: PASS\n";
} finally {
    $cleanup();
}
