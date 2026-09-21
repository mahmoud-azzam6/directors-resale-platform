<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\AttributeDefinitionRepository;
use App\Modules\Property\Repositories\PropertyCatalogRepository;
use App\Modules\Property\Repositories\UnitTypeConfigurationRepository;
use App\Modules\Property\Seeds\AttributeDefinitionsSeedPackage;
use App\Modules\Property\Seeds\AttributeOptionsSeedPackage;
use App\Modules\Property\Seeds\EgyptGeographySeedPackage;
use App\Modules\Property\Seeds\MeasurementDefinitionsSeedPackage;
use App\Modules\Property\Seeds\PropertyCategoriesSeedPackage;
use App\Modules\Property\Seeds\UnitTypeConfigurationsSeedPackage;
use App\Modules\Property\Seeds\UnitTypesSeedPackage;
use App\Modules\Property\Services\GeographicLocationService;
use App\Modules\Property\Services\PropertyCatalogSeedRunner;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Modules\Property\Services\PropertyFormProjectionService;
use App\Modules\Property\Services\UnitTypeConfigurationService;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function bf014FormAssert(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function bf014FormEqual(mixed $actual, mixed $expected, string $message): void
{
    bf014FormAssert($actual === $expected, $message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}

function bf014FormErrors(callable $operation, string $code, string $message): void
{
    try {
        $operation();
    } catch (ValidationException $exception) {
        bf014FormEqual($exception->errors(), [$code => $code], $message);
        return;
    }
    throw new RuntimeException($message . ': expected validation error');
}

function bf014FormExecutions(PDO $pdo): int
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
$databaseName = 'directors_resale_platform_bf014_form_projection_test_' . getmypid();
$safeName = static fn (string $name): bool => preg_match('/^directors_resale_platform_bf014_form_projection_test_[0-9]+$/D', $name) === 1;
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']),
    (string) $connection['username'], (string) $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
$database = null;
$cleanup = static function () use ($server, $databaseName, $developmentDatabase, $safeName, &$created, &$database): void {
    if (! $created) {
        return;
    }
    if ($database !== null && $database->connection()->inTransaction()) {
        $database->rollback();
    }
    bf014FormAssert($safeName($databaseName) && $databaseName !== $developmentDatabase, 'Unsafe cleanup refused');
    $server->exec("DROP DATABASE `{$databaseName}`");
    $created = false;
};
register_shutdown_function($cleanup);

try {
    bf014FormAssert(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB required');
    bf014FormAssert($safeName($databaseName) && $databaseName !== $developmentDatabase, 'Unsafe test database name');
    $server->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $config['default'] = 'mysql';
    $config['connections']['mysql']['database'] = $databaseName;
    $database = new DatabaseManager($config);
    $pdo = $database->connection();
    $migrations = glob($root . '/database/migrations/*.sql') ?: [];
    sort($migrations, SORT_STRING);
    foreach ($migrations as $migration) {
        $pdo->exec(file_get_contents($migration));
    }

    $container = new Container();
    (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $database);
    $catalogService = $container->make(PropertyCatalogService::class);
    $configurationService = $container->make(UnitTypeConfigurationService::class);
    $geographyService = $container->make(GeographicLocationService::class);
    $runner = $container->make(PropertyCatalogSeedRunner::class);
    $projection = $container->make(PropertyFormProjectionService::class);
    $catalogs = $container->make(PropertyCatalogRepository::class);
    $attributes = $container->make(AttributeDefinitionRepository::class);
    $configurations = $container->make(UnitTypeConfigurationRepository::class);
    $ulids = $container->make(UlidGeneratorInterface::class);
    $packages = [
        PropertyCategoriesSeedPackage::make($catalogService), UnitTypesSeedPackage::make($catalogService),
        MeasurementDefinitionsSeedPackage::make($catalogService), AttributeDefinitionsSeedPackage::make($catalogService),
        AttributeOptionsSeedPackage::make($catalogService), UnitTypeConfigurationsSeedPackage::make($catalogService, $configurationService),
        EgyptGeographySeedPackage::make($geographyService),
    ];
    $runner->run($packages);
    $units = array_column($catalogs->listUnitTypes(['limit' => 100]), null, 'code');
    $categories = array_column($catalogs->listCategories(['limit' => 100]), null, 'id');
    $matrix = UnitTypeConfigurationsSeedPackage::matrix();
    $expectedCategories = ['APARTMENT' => 'RESIDENTIAL', 'VILLA' => 'RESIDENTIAL', 'RESIDENTIAL_LAND' => 'LAND', 'COMMERCIAL_UNIT' => 'COMMERCIAL'];

    foreach ($expectedCategories as $code => $categoryCode) {
        $form = $projection->projectForUnitType($units[$code]['id']);
        bf014FormAssert($form !== null, $code . ' projection');
        bf014FormEqual($form['unit_type']['id'], $units[$code]['id'], $code . ' Unit Type ID');
        bf014FormEqual($form['unit_type']['code'], $code, $code . ' Unit Type code');
        bf014FormEqual($form['unit_type']['category']['id'], $units[$code]['property_category_id'], $code . ' Category ID');
        bf014FormEqual($form['unit_type']['category']['code'], $categoryCode, $code . ' Category code');
        bf014FormEqual($form['configuration']['version_number'], 1, $code . ' configuration version');
        bf014FormEqual($form['configuration']['status'], 'active', $code . ' active configuration');
        bf014FormEqual($form['configuration']['provenance'], 'SYSTEM_SEED', $code . ' configuration provenance');
        bf014FormEqual(array_map(static fn (array $row): array => [$row['code'], $row['required'], $row['primary']], $form['measurements']), array_map(static fn (array $row): array => [$row[0], $row[1] === 'REQUIRED', $row[2]], $matrix[$code]['measurements']), $code . ' Measurement mapping');
        bf014FormEqual(array_map(static fn (array $row): array => [$row['code'], $row['required']], $form['attributes']), array_map(static fn (array $row): array => [$row[0], $row[1] === 'REQUIRED'], $matrix[$code]['attributes']), $code . ' Attribute mapping');
        foreach ($form['attributes'] as $attribute) {
            if ($attribute['data_type'] !== 'ENUM') {
                bf014FormEqual($attribute['options'], [], $code . ' non-ENUM options');
            }
        }
    }
    $apartment = $projection->projectForUnitType($units['APARTMENT']['id']);
    bf014FormEqual(array_column($apartment['attributes'], 'data_type', 'code'), [
        'BEDROOMS' => 'INTEGER', 'BATHROOMS' => 'INTEGER', 'FLOOR' => 'INTEGER', 'PARKING_SPACES' => 'INTEGER',
        'FURNISHING' => 'ENUM', 'FINISHING' => 'ENUM', 'VIEW' => 'TEXT', 'BALCONY' => 'BOOLEAN', 'STORAGE' => 'BOOLEAN',
        'DELIVERY_STATUS' => 'ENUM', 'DELIVERY_DATE' => 'DATE', 'YEAR_BUILT' => 'INTEGER',
    ], 'APARTMENT semantic types');
    $options = [];
    foreach ($apartment['attributes'] as $attribute) {
        if ($attribute['data_type'] === 'ENUM') {
            $options[$attribute['code']] = array_column($attribute['options'], 'code');
        }
    }
    bf014FormEqual($options, ['FURNISHING' => ['UNFURNISHED', 'SEMI_FURNISHED', 'FURNISHED'], 'FINISHING' => ['UNFINISHED', 'SEMI_FINISHED', 'FINISHED', 'LUXURY_FINISHED'], 'DELIVERY_STATUS' => ['READY', 'UNDER_CONSTRUCTION']], 'APARTMENT ENUM options');
    $before = bf014FormExecutions($pdo);
    $projection->projectForUnitType($units['APARTMENT']['id']);
    bf014FormEqual(bf014FormExecutions($pdo) - $before, 8, 'Bounded projection query count');

    $activeConfiguration = $configurations->findActiveConfigurationForUnitType($units['APARTMENT']['id']);
    $baseline = $projection->projectForUnitType($units['APARTMENT']['id']);
    $draft = $configurations->createConfigurationVersion(['ulid' => $ulids->generate(), 'unit_type_id' => $units['APARTMENT']['id'], 'version_number' => 2, 'status' => 'draft', 'provenance' => 'SYSTEM_ADMIN']);
    $historical = $configurations->createConfigurationVersion(['ulid' => $ulids->generate(), 'unit_type_id' => $units['APARTMENT']['id'], 'version_number' => 3, 'status' => 'historical', 'provenance' => 'SYSTEM_ADMIN']);
    bf014FormAssert($draft['id'] > 0 && $historical['id'] > 0 && $activeConfiguration !== null, 'Draft and historical setup');
    bf014FormEqual($projection->projectForUnitType($units['APARTMENT']['id']), $baseline, 'Draft and historical isolation');

    bf014FormEqual($projection->projectForUnitType(999999), null, 'Missing Unit Type');
    $catalogs->updateUnitType($units['VILLA']['id'], ['status' => 'inactive']);
    bf014FormErrors(fn () => $projection->projectForUnitType($units['VILLA']['id']), 'CATALOG_ITEM_INACTIVE', 'Inactive Unit Type');
    $noConfigUnit = $catalogs->createUnitType(['ulid' => $ulids->generate(), 'code' => 'NO_ACTIVE', 'name_ar' => 'No active', 'name_en' => 'No active', 'property_category_id' => $units['APARTMENT']['property_category_id'], 'status' => 'active', 'provenance' => 'SYSTEM_ADMIN']);
    bf014FormErrors(fn () => $projection->projectForUnitType($noConfigUnit['id']), 'NO_ACTIVE_CONFIGURATION', 'No ACTIVE Configuration');
    $draftOnly = $catalogs->createUnitType(['ulid' => $ulids->generate(), 'code' => 'DRAFT_ONLY', 'name_ar' => 'Draft only', 'name_en' => 'Draft only', 'property_category_id' => $units['APARTMENT']['property_category_id'], 'status' => 'active', 'provenance' => 'SYSTEM_ADMIN']);
    $configurations->createConfigurationVersion(['ulid' => $ulids->generate(), 'unit_type_id' => $draftOnly['id'], 'version_number' => 1, 'status' => 'draft', 'provenance' => 'SYSTEM_ADMIN']);
    bf014FormErrors(fn () => $projection->projectForUnitType($draftOnly['id']), 'NO_ACTIVE_CONFIGURATION', 'DRAFT-only Configuration');
    $historicalOnly = $catalogs->createUnitType(['ulid' => $ulids->generate(), 'code' => 'HISTORICAL_ONLY', 'name_ar' => 'Historical only', 'name_en' => 'Historical only', 'property_category_id' => $units['APARTMENT']['property_category_id'], 'status' => 'active', 'provenance' => 'SYSTEM_ADMIN']);
    $configurations->createConfigurationVersion(['ulid' => $ulids->generate(), 'unit_type_id' => $historicalOnly['id'], 'version_number' => 1, 'status' => 'historical', 'provenance' => 'SYSTEM_ADMIN']);
    bf014FormErrors(fn () => $projection->projectForUnitType($historicalOnly['id']), 'NO_ACTIVE_CONFIGURATION', 'HISTORICAL-only Configuration');

    $furnishing = array_values(array_filter($apartment['attributes'], static fn (array $attribute): bool => $attribute['code'] === 'FURNISHING'))[0];
    $attributes->updateAttributeOption($furnishing['definition_id'], $furnishing['options'][0]['id'], ['status' => 'inactive']);
    $afterInactiveOption = $projection->projectForUnitType($units['APARTMENT']['id']);
    $furnishingAfter = array_values(array_filter($afterInactiveOption['attributes'], static fn (array $attribute): bool => $attribute['code'] === 'FURNISHING'))[0];
    bf014FormEqual(array_column($furnishingAfter['options'], 'code'), ['SEMI_FURNISHED', 'FURNISHED'], 'Inactive ENUM option excluded');
    foreach ($furnishingAfter['options'] as $option) {
        $attributes->updateAttributeOption($furnishingAfter['definition_id'], $option['id'], ['status' => 'inactive']);
    }
    bf014FormErrors(fn () => $projection->projectForUnitType($units['APARTMENT']['id']), 'ENUM_HAS_NO_ACTIVE_OPTIONS', 'Unusable ENUM state');
    echo "BF014.6B PropertyFormProjectionService MariaDB acceptance: PASS\n";
} finally {
    $cleanup();
}
