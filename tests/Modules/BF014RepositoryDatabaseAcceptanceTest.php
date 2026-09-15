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

function bf014RepoAssert(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
}

function bf014RepoEqual(mixed $actual, mixed $expected, string $message): void
{
    bf014RepoAssert($actual === $expected, $message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}

function bf014RepoThrows(callable $operation, string $class, string $message, ?int $driverCode = null): void
{
    try { $operation(); }
    catch (Throwable $exception) {
        bf014RepoAssert($exception instanceof $class, $message . ': unexpected ' . $exception::class . ': ' . $exception->getMessage());
        if ($driverCode !== null) {
            bf014RepoEqual((int) ($exception->errorInfo[1] ?? 0), $driverCode, $message . ' MariaDB error');
        }
        return;
    }
    throw new RuntimeException($message . ': expected ' . $class);
}

function bf014RepoIds(array $rows): array { return array_column($rows, 'id'); }

/** Observe actual server-side native prepared executions; no database replacement. */
function bf014RepoExecutions(PDO $pdo): int
{
    // PDO::query itself uses native preparation when emulation is off. Use the
    // text protocol only for this fixed observer statement, then restore it.
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
$databaseName = 'directors_resale_platform_bf014_repo_test_' . getmypid();
$safeName = static fn (string $name): bool => preg_match('/^directors_resale_platform_bf014_repo_test_[0-9]+$/D', $name) === 1;
bf014RepoAssert($safeName($databaseName) && $databaseName !== $developmentDatabase, 'Unsafe test database name');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']),
    (string) $connection['username'], (string) $connection['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
$created = false;
$database = $otherDatabase = null;
$cleanup = static function () use ($server, $databaseName, $developmentDatabase, $safeName, &$created, &$database, &$otherDatabase): void {
    if (! $created) { return; }
    foreach ([$database, $otherDatabase] as $manager) {
        if ($manager !== null && $manager->connection()->inTransaction()) { $manager->rollback(); }
    }
    bf014RepoAssert($safeName($databaseName) && $databaseName !== $developmentDatabase, 'Unsafe cleanup refused');
    $server->exec("DROP DATABASE `{$databaseName}`");
    $statement = $server->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = ?');
    $statement->execute([$databaseName]);
    bf014RepoEqual((int) $statement->fetchColumn(), 0, 'Disposable database cleanup');
    $created = false;
    echo "remaining_bf014_repo_test_database=0\n";
};
register_shutdown_function($cleanup);

try {
    $version = (string) $server->query('SELECT VERSION()')->fetchColumn();
    bf014RepoAssert(str_contains($version, 'MariaDB'), 'Real MariaDB required');
    echo "MariaDB={$version}\ndisposable_database={$databaseName}\n";
    // No IF NOT EXISTS: never adopt or drop a pre-existing database.
    $server->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $config['default'] = 'mysql';
    $config['connections']['mysql']['database'] = $databaseName;
    $database = new DatabaseManager($config);
    $pdo = $database->connection();
    $pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    $pdo->exec('SET SESSION foreign_key_checks = 1, check_constraint_checks = 1');
    $migrations = glob($root . '/database/migrations/*.sql') ?: [];
    sort($migrations, SORT_STRING);
    bf014RepoEqual(array_map(static fn ($file) => (int) substr(basename($file), 0, 3), $migrations), range(1, 31), 'Migrations 001-031');
    foreach ($migrations as $file) { $pdo->exec(file_get_contents($file)); }
    $tables = ['property_categories', 'unit_types', 'unit_type_configuration_versions', 'measurement_definitions',
        'unit_type_measurement_rules', 'attribute_definitions', 'attribute_options', 'unit_type_attribute_rules',
        'geographic_locations', 'developers', 'projects', 'project_phases', 'property_catalog_seed_versions'];
    foreach ($tables as $table) {
        bf014RepoEqual((int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn(), 0, $table . ' exists without baseline rows');
    }
    echo "Migrations 001-031 / 13 empty BF014 tables: PASS\n";

    $container = new Container();
    (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $database);
    $classes = [PropertyCatalogRepository::class, MeasurementDefinitionRepository::class, AttributeDefinitionRepository::class,
        UnitTypeConfigurationRepository::class, GeographicLocationRepository::class, DevelopmentCatalogRepository::class];
    $repositories = array_map(static fn ($class) => $container->make($class), $classes);
    foreach ($repositories as $index => $repository) { bf014RepoAssert($repository instanceof $classes[$index], 'Container binding'); }
    [$catalog, $measurements, $attributes, $configurations, $geography, $development] = $repositories;
    $ulids = $container->make(UlidGeneratorInterface::class);
    $data = static fn (string $code, array $extra = []): array => array_replace([
        'ulid' => $ulids->generate(), 'code' => $code, 'name_ar' => 'اسم ' . $code, 'name_en' => 'Name ' . $code,
        'status' => 'active', 'provenance' => 'SYSTEM_ADMIN', 'sort_order' => 0,
    ], $extra);
    $missing = 999999999;

    // Common public CRUD/list contracts, exercised against separate real table families.
    $category = $catalog->createCategory($data('BASE_CATEGORY'));
    $families = [
        [$catalog, 'Category', 'Categories', []],
        [$catalog, 'UnitType', 'UnitTypes', ['property_category_id' => $category['id']]],
        [$measurements, 'MeasurementDefinition', 'MeasurementDefinitions', ['default_unit_code' => 'SQM']],
        [$attributes, 'AttributeDefinition', 'AttributeDefinitions', ['data_type' => 'TEXT', 'text_max_length' => 80]],
        [$geography, 'GeographicLocation', 'GeographicLocations', ['location_type' => 'COUNTRY']],
        [$development, 'Developer', 'Developers', []],
        [$development, 'Project', 'Projects', []],
    ];
    foreach ($families as [$repo, $stem, $plural, $extra]) {
        $create = 'create' . $stem;
        $find = 'find' . $stem . 'ById';
        $update = 'update' . $stem;
        $delete = 'delete' . $stem;
        $list = 'list' . $plural;
        $prefix = strtoupper($stem) . 'CASE';
        $rows = [];
        foreach ([['%', 2, 'active'], ['_', 1, 'active'], ['!', 1, 'inactive'], ['PLAIN', 3, 'active']] as [$suffix, $sort, $status]) {
            $rows[] = $repo->$create($data($prefix . $suffix, $extra + ['sort_order' => $sort, 'status' => $status]));
        }
        $row = $rows[0];
        bf014RepoEqual($repo->$find((string) $row['id']), $row, $stem . ' ID');
        bf014RepoEqual($repo->{'find' . $stem . 'ByUlid'}($row['ulid']), $row, $stem . ' ULID');
        bf014RepoEqual($repo->{'find' . $stem . 'ByCode'}($row['code']), $row, $stem . ' code');
        bf014RepoEqual($repo->{lcfirst($stem) . 'ExistsByCode'}($row['code']), true, $stem . ' exists');
        bf014RepoEqual($repo->{lcfirst($stem) . 'ExistsByCode'}('ABSENT'), false, $stem . ' absent');
        bf014RepoAssert(is_int($row['id']) && is_int($row['sort_order']), $stem . ' integer mapping');
        bf014RepoEqual($row['created_by_user_id'], null, $stem . ' nullable creator');
        bf014RepoEqual($row['updated_by_user_id'], null, $stem . ' nullable updater');
        foreach (['created_at', 'updated_at'] as $field) {
            bf014RepoAssert(is_string($row[$field]) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $row[$field]) === 1, $stem . ' timestamp');
        }
        bf014RepoEqual(bf014RepoIds($repo->$list(['search' => $prefix])), [$rows[1]['id'], $rows[2]['id'], $rows[0]['id'], $rows[3]['id']], $stem . ' sort/id order');
        bf014RepoEqual(bf014RepoIds($repo->$list(['search' => $prefix, 'status' => 'inactive'])), [$rows[2]['id']], $stem . ' grouped status/search');
        bf014RepoEqual(bf014RepoIds($repo->$list(['search' => $prefix, 'limit' => 1, 'offset' => 1])), [$rows[2]['id']], $stem . ' pagination');
        foreach (['%', '_', '!'] as $index => $literal) {
            bf014RepoEqual(bf014RepoIds($repo->$list(['search' => $prefix . $literal])), [$rows[$index]['id']], $stem . ' literal ' . $literal);
        }
        bf014RepoEqual($repo->$list(['search' => "' OR 1=1 --"]), [], $stem . ' bound hostile search');
        bf014RepoAssert(count($repo->$list(['status' => 'inactive'])) >= 1, $stem . ' QueryBuilder list');
        bf014RepoEqual($repo->$update($row['id'], []), $row, $stem . ' empty update');
        bf014RepoEqual($repo->$update($row['id'], ['name_en' => $row['name_en']]), $row, $stem . ' unchanged update');
        $changed = $repo->$update($row['id'], ['name_ar' => 'تعديل', 'name_en' => 'Changed', 'sort_order' => 9, 'status' => 'inactive', 'updated_by_user_id' => null]);
        bf014RepoEqual($changed['name_en'], 'Changed', $stem . ' update');
        bf014RepoEqual($changed['sort_order'], 9, $stem . ' sort update');
        foreach (['ulid', 'code', 'provenance', 'created_by_user_id', 'unknown', 'updated_at'] as $field) {
            $before = bf014RepoExecutions($pdo);
            bf014RepoThrows(fn () => $repo->$update($row['id'], [$field => 'forbidden']), InvalidArgumentException::class, $stem . ' immutable ' . $field);
            bf014RepoEqual(bf014RepoExecutions($pdo), $before, $stem . ' rejected update executes no SQL');
        }
        foreach (['id', 'created_at', 'unknown'] as $field) {
            $before = bf014RepoExecutions($pdo);
            bf014RepoThrows(fn () => $repo->$create($data('INVALID_CREATE', $extra + [$field => 1])), InvalidArgumentException::class, $stem . ' create read-only ' . $field);
            bf014RepoEqual(bf014RepoExecutions($pdo), $before, $stem . ' rejected create executes no SQL');
        }
        bf014RepoThrows(fn () => $repo->$create($data($row['code'], $extra)), PDOException::class, $stem . ' duplicate', 1062);
        bf014RepoEqual($repo->$find($row['id']), $changed, $stem . ' recovery after DB exception');
        bf014RepoEqual($repo->$update($missing, []), null, $stem . ' missing update');
        foreach ($rows as $item) { bf014RepoEqual($repo->$delete($item['id']), true, $stem . ' delete'); }
        bf014RepoEqual($repo->$delete($row['id']), false, $stem . ' missing delete');
        echo $stem . " CRUD / lists / escaping / mapping / write policy: PASS\n";
    }

    $category2 = $catalog->createCategory($data('SECOND_CATEGORY'));
    $unit = $catalog->createUnitType($data('UNIT', ['property_category_id' => $category['id']]));
    $unit2 = $catalog->createUnitType($data('UNIT2', ['property_category_id' => $category2['id']]));
    bf014RepoEqual($unit['last_allocated_configuration_version'], 0, 'Unit Type allocation default mapping');
    bf014RepoThrows(fn () => $catalog->updateUnitType($unit['id'], ['last_allocated_configuration_version' => 1]), InvalidArgumentException::class, 'Generic Unit Type allocation update rejected');
    bf014RepoEqual($catalog->updateLastAllocatedConfigurationVersion($unit['id'], 7)['last_allocated_configuration_version'], 7, 'Unit Type allocation persistence');
    bf014RepoThrows(fn () => $catalog->updateLastAllocatedConfigurationVersion($unit['id'], -1), InvalidArgumentException::class, 'Negative allocation rejected');
    bf014RepoEqual(bf014RepoIds($catalog->listUnitTypesByCategory($category['id'], ['search' => 'UNIT', 'status' => 'active'])), [$unit['id']], 'Category scoped units');
    bf014RepoEqual($catalog->updateUnitType($unit['id'], ['property_category_id' => $category2['id']])['property_category_id'], $category2['id'], 'Category reassignment');
    bf014RepoEqual($catalog->listUnitTypesByCategory($category['id']), [], 'Reassignment removes old scope');
    bf014RepoThrows(fn () => $catalog->deleteCategory($category2['id']), PDOException::class, 'Referenced category', 1451);
    bf014RepoThrows(fn () => $catalog->createUnitType($data('BAD_UNIT', ['property_category_id' => $missing])), PDOException::class, 'Unit FK', 1452);
    $m1 = $measurements->createMeasurementDefinition($data('M1', ['default_unit_code' => 'SQM']));
    $m2 = $measurements->createMeasurementDefinition($data('M2', ['default_unit_code' => 'SQM', 'status' => 'inactive']));
    bf014RepoEqual($m1['default_unit_code'], 'SQM', 'Unit mapping');
    bf014RepoThrows(fn () => $measurements->updateMeasurementDefinition($m1['id'], ['default_unit_code' => 'INVALID']), PDOException::class, 'Default unit CHECK', 4025);
    $definitions = [];
    foreach (['INTEGER', 'DECIMAL', 'BOOLEAN', 'TEXT', 'ENUM', 'DATE'] as $type) {
        $definitions[$type] = $attributes->createAttributeDefinition($data('A_' . $type, ['data_type' => $type, 'text_max_length' => $type === 'TEXT' ? 120 : null]));
        bf014RepoEqual($definitions[$type]['text_max_length'], $type === 'TEXT' ? 120 : null, 'Text length mapping');
        bf014RepoEqual(bf014RepoIds($attributes->listAttributeDefinitions(['data_type' => $type])), [$definitions[$type]['id']], 'Data type filter');
    }
    $enum = $definitions['ENUM'];
    $enum2 = $attributes->createAttributeDefinition($data('A_ENUM2', ['data_type' => 'ENUM']));
    $option = $attributes->createAttributeOption($enum['id'], $data('SHARED', ['status' => 'inactive', 'sort_order' => 2]));
    $option2 = $attributes->createAttributeOption($enum['id'], $data('OTHER', ['sort_order' => 1]));
    $foreignOption = $attributes->createAttributeOption($enum2['id'], $data('SHARED'));
    bf014RepoEqual($attributes->findAttributeOptionById($option['id']), $option, 'Option ID');
    bf014RepoEqual($attributes->findAttributeOptionByUlid($option['ulid']), $option, 'Option ULID');
    bf014RepoEqual($attributes->findAttributeOptionByCode($enum['id'], 'SHARED'), $option, 'Option scoped code');
    bf014RepoEqual($attributes->findAttributeOptionByCode($enum2['id'], 'SHARED'), $foreignOption, 'Option cross-definition code');
    bf014RepoEqual($attributes->attributeOptionExistsByCode($enum['id'], 'SHARED'), true, 'Option exists');
    bf014RepoEqual($attributes->attributeOptionExistsByCode($enum2['id'], 'OTHER'), false, 'Option existence scope');
    bf014RepoEqual(bf014RepoIds($attributes->listAttributeOptions($enum['id'])), [$option2['id'], $option['id']], 'Option order');
    bf014RepoEqual($attributes->updateAttributeOption($enum2['id'], $option['id'], ['name_en' => 'bad']), null, 'Option update scope');
    bf014RepoEqual($attributes->updateAttributeOption($enum2['id'], $option['id'], []), null, 'Option reread scope');
    bf014RepoEqual($attributes->deleteAttributeOption($enum2['id'], $option['id']), false, 'Option delete scope');
    bf014RepoEqual($attributes->updateAttributeOption($enum['id'], $option['id'], ['name_en' => 'Updated'])['name_en'], 'Updated', 'Option update');
    bf014RepoThrows(fn () => $attributes->createAttributeOption($enum['id'], $data('SHARED')), PDOException::class, 'Option duplicate', 1062);
    bf014RepoEqual($attributes->deleteAttributeOption($enum2['id'], $foreignOption['id']), true, 'Option delete');

    foreach ([[$measurements, 'findMeasurementDefinitionsByIds', [$m1['id'], $m2['id']]],
        [$attributes, 'findAttributeDefinitionsByIds', [$enum['id'], $enum2['id']]]] as [$repo, $method, $ids]) {
        $before = bf014RepoExecutions($pdo);
        bf014RepoEqual($repo->$method([]), [], 'Empty batch');
        bf014RepoEqual(bf014RepoExecutions($pdo), $before, 'Empty batch no SQL');
        bf014RepoEqual(bf014RepoIds($repo->$method([$ids[1], $ids[0], $ids[1], $missing])), $ids, 'Batch dedup/order/missing');
        bf014RepoEqual(bf014RepoExecutions($pdo) - $before, 1, 'One real batch query');
    }
    bf014RepoEqual($attributes->listAttributeOptionsForDefinitions([]), [], 'Empty options batch');
    bf014RepoEqual(bf014RepoIds($attributes->listAttributeOptionsForDefinitions([$enum2['id'], $enum['id']])), [$option2['id'], $option['id']], 'Options batch scope/order');
    $before = bf014RepoExecutions($pdo);
    bf014RepoThrows(fn () => $measurements->findMeasurementDefinitionsByIds([$m1['id'], 'invalid']), InvalidArgumentException::class, 'Batch validates all IDs before construction');
    bf014RepoThrows(fn () => $attributes->findAttributeDefinitionsByIds([$enum['id'], 0]), InvalidArgumentException::class, 'Attribute batch validation');
    bf014RepoThrows(fn () => $attributes->listAttributeOptionsForDefinitions([$enum['id'], 0]), InvalidArgumentException::class, 'Option batch validation');
    bf014RepoEqual(bf014RepoExecutions($pdo), $before, 'Rejected batches execute no SQL');
    bf014RepoEqual($measurements->findMeasurementDefinitionById($m1['id']), $m1, 'Batch failure recovery');
    echo "Definitions / scoped options / batches: PASS\n";

    $configData = static fn (int $unitId, int $version, string $status = 'draft'): array => [
        'ulid' => $ulids->generate(), 'unit_type_id' => $unitId, 'version_number' => $version, 'status' => $status, 'provenance' => 'SYSTEM_ADMIN',
    ];
    bf014RepoEqual($configurations->findLatestVersionNumberForUnitType($unit['id']), 0, 'No versions');
    $c1 = $configurations->createConfigurationVersion($configData($unit['id'], 1, 'active'));
    $c2 = $configurations->createConfigurationVersion($configData($unit['id'], 7));
    bf014RepoEqual($configurations->findConfigurationById($c1['id']), $c1, 'Configuration ID');
    bf014RepoEqual($configurations->findConfigurationByUlid($c1['ulid']), $c1, 'Configuration ULID');
    bf014RepoEqual($configurations->findConfigurationByVersion($unit['id'], 1), $c1, 'Configuration version');
    bf014RepoEqual($configurations->findActiveConfigurationForUnitType($unit['id']), $c1, 'Active configuration');
    bf014RepoEqual($configurations->findDraftConfigurationForUnitType($unit['id']), $c2, 'Draft configuration');
    bf014RepoEqual($configurations->findDraftConfigurationForUpdate($unit['id']), $c2, 'Draft configuration lock');
    bf014RepoEqual(bf014RepoIds($configurations->listConfigurationsForUnitType($unit['id'])), [$c2['id'], $c1['id']], 'Versions descending');
    bf014RepoEqual(bf014RepoIds($configurations->listConfigurationsForUnitType($unit['id'], ['status' => 'draft'])), [$c2['id']], 'Version status');
    bf014RepoEqual($configurations->findLatestVersionNumberForUnitType($unit['id']), 7, 'Latest persisted, no +1');
    bf014RepoEqual($configurations->updateConfigurationVersion($c1['id'], []), $c1, 'Configuration empty update');
    bf014RepoThrows(fn () => $configurations->createConfigurationVersion($configData($unit['id'], 7)), PDOException::class, 'Duplicate version', 1062);
    bf014RepoThrows(fn () => $configurations->createConfigurationVersion($configData($unit['id'], 8, 'active')), PDOException::class, 'One active guard', 1062);
    bf014RepoThrows(fn () => $configurations->createConfigurationVersion($configData($missing, 1)), PDOException::class, 'Configuration FK', 1452);
    bf014RepoThrows(fn () => $configurations->updateConfigurationVersion($c2['id'], ['status' => 'active']), PDOException::class, 'Active update guard', 1062);
    bf014RepoEqual($configurations->updateConfigurationVersion($c2['id'], ['status' => 'historical', 'updated_by_user_id' => null])['status'], 'historical', 'Status update');
    $c3 = $configurations->createConfigurationVersion($configData($unit['id'], 19));
    bf014RepoEqual($configurations->deleteConfigurationVersion($c3['id']), true, 'Delete unused configuration');
    bf014RepoEqual($configurations->findLatestVersionNumberForUnitType($unit['id']), 7, 'Latest follows persisted rows, no high-water assumption');

    $rules = [];
    foreach ([['Measurement', 'measurement_definition_id', [$m1, $m2]], ['Attribute', 'attribute_definition_id', [$enum, $definitions['TEXT']]]] as [$stem, $field, $defs]) {
        $create = 'create' . $stem . 'Rule';
        $update = 'update' . $stem . 'Rule';
        $delete = 'delete' . $stem . 'Rule';
        $list = 'list' . $stem . 'Rules';
        $rows = [];
        foreach ($defs as $index => $definition) {
            $values = [$field => $definition['id'], 'requirement' => 'REQUIRED', 'sort_order' => 2 - $index];
            if ($stem === 'Measurement') { $values['is_primary'] = $index === 0 ? 1 : 0; }
            $rows[] = $configurations->$create($c1['id'], $values);
        }
        $rule = $rows[0];
        bf014RepoEqual($configurations->{'find' . $stem . 'RuleById'}($c1['id'], $rule['id']), $rule, $stem . ' rule ID');
        bf014RepoEqual($configurations->{'find' . $stem . 'RuleByDefinition'}($c1['id'], $defs[0]['id']), $rule, $stem . ' rule definition');
        bf014RepoEqual(bf014RepoIds($configurations->$list($c1['id'])), [$rows[1]['id'], $rows[0]['id']], $stem . ' rule order');
        bf014RepoEqual($configurations->$update($c1['id'], $rule['id'], []), $rule, $stem . ' empty update');
        bf014RepoEqual($configurations->$update($c2['id'], $rule['id'], ['sort_order' => 99]), null, $stem . ' update scope');
        bf014RepoEqual($configurations->$update($c2['id'], $rule['id'], []), null, $stem . ' empty reread scope');
        bf014RepoEqual($configurations->$delete($c2['id'], $rule['id']), false, $stem . ' delete scope');
        bf014RepoEqual($configurations->$update($c1['id'], $rule['id'], ['sort_order' => 5])['sort_order'], 5, $stem . ' update');
        $duplicate = [$field => $defs[0]['id'], 'requirement' => 'REQUIRED'];
        if ($stem === 'Measurement') { $duplicate['is_primary'] = 0; }
        bf014RepoThrows(fn () => $configurations->$create($c1['id'], $duplicate), PDOException::class, $stem . ' duplicate rule', 1062);
        $temporary = $configurations->$create($c2['id'], $duplicate);
        bf014RepoEqual($configurations->$delete($c2['id'], $temporary['id']), true, $stem . ' scoped delete');
        $rules[$stem] = $rows;
    }
    bf014RepoEqual($rules['Measurement'][0]['is_primary'], true, 'Primary boolean');
    bf014RepoEqual($rules['Measurement'][1]['is_primary'], false, 'Nonprimary boolean');
    bf014RepoThrows(fn () => $configurations->updateMeasurementRule($c1['id'], $rules['Measurement'][1]['id'], ['is_primary' => 1]), PDOException::class, 'One primary', 1062);
    bf014RepoThrows(fn () => $configurations->updateMeasurementRule($c1['id'], $rules['Measurement'][0]['id'], ['requirement' => 'OPTIONAL']), PDOException::class, 'Primary REQUIRED', 4025);
    bf014RepoThrows(fn () => $configurations->updateMeasurementRule($c1['id'], $rules['Measurement'][1]['id'], ['is_primary' => 2]), PDOException::class, 'Boolean CHECK', 4025);
    bf014RepoThrows(fn () => $measurements->deleteMeasurementDefinition($m1['id']), PDOException::class, 'Referenced measurement', 1451);
    bf014RepoThrows(fn () => $attributes->deleteAttributeDefinition($enum['id']), PDOException::class, 'Referenced attribute', 1451);
    bf014RepoThrows(fn () => $configurations->deleteConfigurationVersion($c1['id']), PDOException::class, 'Referenced configuration', 1451);
    bf014RepoThrows(fn () => $configurations->createMeasurementRule($c2['id'], ['measurement_definition_id' => $missing, 'requirement' => 'OPTIONAL', 'is_primary' => 0]), PDOException::class, 'Measurement definition FK', 1452);
    bf014RepoThrows(fn () => $configurations->createAttributeRule($c2['id'], ['attribute_definition_id' => $missing, 'requirement' => 'OPTIONAL']), PDOException::class, 'Attribute definition FK', 1452);
    // Invalid stored representations are impossible through the enforced DB CHECK; inspect mapping separately.
    $boolean = new ReflectionMethod(UnitTypeConfigurationRepository::class, 'storedBoolean');
    $boolean->setAccessible(true);
    foreach ([[0, false], ['0', false], [1, true], ['1', true]] as [$value, $expected]) {
        bf014RepoEqual($boolean->invoke($configurations, $value), $expected, 'Strict stored boolean');
    }
    foreach ([false, true, null, 'false', 'true', 2, -1, ''] as $value) {
        bf014RepoThrows(fn () => $boolean->invoke($configurations, $value), RuntimeException::class, 'Invalid stored boolean');
    }

    $before = bf014RepoExecutions($pdo);
    $aggregate = $configurations->findConfigurationAggregateById($c1['id']);
    bf014RepoEqual(bf014RepoExecutions($pdo) - $before, 6, 'Aggregate actual native query count');
    bf014RepoEqual(array_keys($aggregate), ['configuration', 'measurement_rules', 'attribute_rules'], 'Aggregate shape');
    bf014RepoEqual($aggregate['configuration'], $configurations->findConfigurationById($c1['id']), 'Aggregate configuration');
    foreach (['measurement_rules' => 'Measurement', 'attribute_rules' => 'Attribute'] as $key => $stem) {
        bf014RepoEqual(array_column($aggregate[$key], 'rule'), $configurations->{'list' . $stem . 'Rules'}($c1['id']), 'Aggregate complete ordered ' . $key);
        foreach ($aggregate[$key] as $entry) {
            bf014RepoEqual(array_keys($entry), $stem === 'Measurement' ? ['rule', 'definition'] : ['rule', 'definition', 'options'], 'Aggregate entry shape');
            bf014RepoEqual($entry['definition']['id'], $entry['rule'][strtolower($stem) . '_definition_id'], 'Definition attachment');
        }
    }
    bf014RepoEqual($aggregate['measurement_rules'][0]['definition']['status'], 'inactive', 'Inactive definition retained');
    bf014RepoEqual($aggregate['attribute_rules'][0]['options'], [], 'Non-ENUM empty options');
    bf014RepoEqual(bf014RepoIds($aggregate['attribute_rules'][1]['options']), [$option2['id'], $option['id']], 'ENUM options ordered');
    bf014RepoEqual($aggregate['attribute_rules'][1]['options'][1]['status'], 'inactive', 'Inactive option retained');
    $emptyConfiguration = $configurations->findConfigurationById($c2['id']);
    $before = bf014RepoExecutions($pdo);
    bf014RepoEqual($configurations->findConfigurationAggregateById($c2['id']), ['configuration' => $emptyConfiguration, 'measurement_rules' => [], 'attribute_rules' => []], 'Empty aggregate');
    bf014RepoEqual(bf014RepoExecutions($pdo) - $before, 3, 'Empty aggregate skips all metadata batches');
    $before = bf014RepoExecutions($pdo);
    bf014RepoEqual($configurations->findConfigurationAggregateById($missing), null, 'Missing aggregate');
    bf014RepoEqual(bf014RepoExecutions($pdo) - $before, 1, 'Missing aggregate one query');
    // Cross the default list page size to detect hidden pagination as well as N+1.
    foreach (range(1, 49) as $index) {
        $measurement = $measurements->createMeasurementDefinition($data('SCALE_M' . $index, ['default_unit_code' => 'SQM']));
        $attribute = $attributes->createAttributeDefinition($data('SCALE_A' . $index, ['data_type' => 'INTEGER']));
        $configurations->createMeasurementRule($c1['id'], ['measurement_definition_id' => $measurement['id'], 'requirement' => 'OPTIONAL', 'is_primary' => 0]);
        $configurations->createAttributeRule($c1['id'], ['attribute_definition_id' => $attribute['id'], 'requirement' => 'OPTIONAL']);
    }
    $before = bf014RepoExecutions($pdo);
    $largeAggregate = $configurations->findConfigurationAggregateById($c1['id']);
    bf014RepoEqual(bf014RepoExecutions($pdo) - $before, 6, '51+51 rules still six queries');
    bf014RepoEqual(count($largeAggregate['measurement_rules']), 51, 'No measurement pagination loss');
    bf014RepoEqual(count($largeAggregate['attribute_rules']), 51, 'No attribute pagination loss');
    echo "Configurations / rules / strict booleans / aggregate (6 real queries): PASS\n";
    echo "Missing referenced definitions: prevented by enforced foreign keys; constraints not disabled\n";

    // Second actual DatabaseManager/PDO; one-second server lock timeout bounds waiting.
    $otherDatabase = new DatabaseManager($config);
    $other = $otherDatabase->connection();
    $other->exec('SET SESSION innodb_lock_wait_timeout = 1');
    $otherContainer = new Container();
    (new AppServiceProvider($otherContainer, []))->register();
    $otherContainer->instance(DatabaseConnectionInterface::class, $otherDatabase);
    $otherConfigurations = $otherContainer->make(UnitTypeConfigurationRepository::class);
    bf014RepoAssert($pdo->query('SELECT CONNECTION_ID()')->fetchColumn() !== $other->query('SELECT CONNECTION_ID()')->fetchColumn(), 'Independent connections');
    bf014RepoEqual($pdo->inTransaction(), false, 'Repositories do not auto-begin');
    foreach (['commit', 'rollback'] as $release) {
        $database->beginTransaction();
        try {
            bf014RepoEqual($configurations->findConfigurationForUpdate($c1['id'])['id'], $c1['id'], 'FOR UPDATE row');
            bf014RepoAssert($pdo->inTransaction(), 'Repository preserves caller transaction');
            $statement = $other->prepare('UPDATE unit_type_configuration_versions SET status = status WHERE id = ?');
            bf014RepoThrows(fn () => $statement->execute([$c1['id']]), PDOException::class, 'Blocked concurrent mutation', 1205);
            $database->$release();
            bf014RepoEqual($statement->execute([$c1['id']]), true, 'Mutation after ' . $release);
        } finally { if ($pdo->inTransaction()) { $database->rollback(); } }
    }
    $database->beginTransaction();
    $configurations->updateConfigurationVersion($c2['id'], ['status' => 'draft']);
    bf014RepoAssert($pdo->inTransaction(), 'Write leaves transaction open');
    $database->rollback();
    bf014RepoEqual($configurations->findConfigurationById($c2['id'])['status'], 'historical', 'Caller rollback preserved');
    $database->beginTransaction();
    try {
        $configurations->findConfigurationForUpdate($c1['id']);
        bf014RepoThrows(fn () => $otherConfigurations->findConfigurationForUpdate($c1['id']), PDOException::class, 'Lock read exception', 1205);
        // A leaked FOR UPDATE flag would time out here too; ordinary consistent read must succeed.
        bf014RepoEqual($otherConfigurations->findConfigurationById($c1['id'])['id'], $c1['id'], 'Lock flag resets after failure');
    } finally { $database->rollback(); }
    $database->beginTransaction();
    try {
        $configurations->findConfigurationForUpdate($c1['id']);
        $database->commit();
        $otherDatabase->beginTransaction();
        $otherConfigurations->findConfigurationForUpdate($c1['id']);
        $pdo->exec('SET SESSION innodb_lock_wait_timeout = 1');
        bf014RepoEqual($configurations->findConfigurationById($c1['id'])['id'], $c1['id'], 'Lock flag resets after success');
    } finally {
        if ($pdo->inTransaction()) { $database->rollback(); }
        if ($other->inTransaction()) { $otherDatabase->rollback(); }
    }
    echo "FOR UPDATE / independent connections / commit and rollback ownership: PASS\n";

    $country = $geography->createGeographicLocation($data('G_ROOT', ['location_type' => 'COUNTRY']));
    $country2 = $geography->createGeographicLocation($data('G_ROOT2', ['location_type' => 'COUNTRY']));
    $child = $geography->createGeographicLocation($data('G_CHILD', ['location_type' => 'GOVERNORATE', 'parent_id' => $country['id'], 'sort_order' => 2]));
    $child2 = $geography->createGeographicLocation($data('G_CHILD2', ['location_type' => 'CITY', 'parent_id' => $country['id'], 'sort_order' => 1, 'status' => 'inactive']));
    $leaf = $geography->createGeographicLocation($data('G_LEAF', ['location_type' => 'AREA', 'parent_id' => $child['id']]));
    $tip = $geography->createGeographicLocation($data('G_TIP', ['location_type' => 'DISTRICT', 'parent_id' => $leaf['id']]));
    foreach ([[], ['search' => 'G_']] as $search) {
        bf014RepoEqual(count($geography->listGeographicLocations($search)), 6, 'Omitted parent unrestricted');
        bf014RepoEqual(bf014RepoIds($geography->listGeographicLocations($search + ['parent_id' => null])), [$country['id'], $country2['id']], 'NULL roots');
        bf014RepoEqual(bf014RepoIds($geography->listGeographicLocations($search + ['parent_id' => $country['id']])), [$child2['id'], $child['id']], 'Exact parent');
    }
    bf014RepoEqual(bf014RepoIds($geography->listChildren($country['id'])), [$child2['id'], $child['id']], 'Direct children ordered');
    bf014RepoEqual(bf014RepoIds($geography->listChildren($country['id'], ['status' => 'active', 'location_type' => 'GOVERNORATE', 'search' => 'G_'])), [$child['id']], 'Children combined filters');
    bf014RepoEqual($geography->listChildren($country2['id']), [], 'No cross-parent leakage');
    $ancestry = static function (int $id, int $depth, array $expected, string $reason) use ($geography): void {
        $result = $geography->loadAncestry($id, $depth);
        bf014RepoEqual(bf014RepoIds($result['locations']), $expected, 'Ancestry nodes');
        bf014RepoEqual($result['stop_reason'], $reason, 'Ancestry stop');
    };
    $ancestry($country['id'], 32, [$country['id']], 'root');
    $ancestry($leaf['id'], 32, [$leaf['id'], $child['id'], $country['id']], 'root');
    $ancestry($missing, 32, [], 'missing_parent');
    $chain = [$tip['id'], $leaf['id'], $child['id'], $country['id']];
    foreach (range(1, 4) as $depth) { $ancestry($tip['id'], $depth, array_slice($chain, 0, $depth), $depth === 4 ? 'root' : 'depth_limit'); }
    foreach ([0, 257] as $depth) { bf014RepoThrows(fn () => $geography->loadAncestry($tip['id'], $depth), InvalidArgumentException::class, 'Depth validation'); }
    bf014RepoThrows(fn () => $geography->updateGeographicLocation($leaf['id'], ['parent_id' => $missing]), PDOException::class, 'Missing parent prevented', 1452);
    $geography->updateGeographicLocation($child['id'], ['parent_id' => $leaf['id']]);
    $ancestry($tip['id'], 32, [$tip['id'], $leaf['id'], $child['id']], 'cycle');
    $geography->updateGeographicLocation($child['id'], ['parent_id' => $country['id']]);
    echo "Geography / nullable parent / ancestry root, missing start, cycle, depth: PASS\n";
    echo "Missing parent after start: prevented by FK; constraints not disabled\n";

    $developer = $development->createDeveloper($data('DEV'));
    $developer2 = $development->createDeveloper($data('DEV2'));
    $projects = [];
    foreach ([[$developer['id'], $country['id']], [null, $country['id']], [$developer['id'], null], [null, null], [$developer2['id'], $country2['id']]] as $index => [$dev, $geo]) {
        $projects[] = $development->createProject($data('MATRIX' . $index, ['developer_id' => $dev, 'geographic_location_id' => $geo]));
    }
    $matrix = [
        [[], [0, 1, 2, 3, 4]], [['developer_id' => null], [1, 3]], [['developer_id' => $developer['id']], [0, 2]],
        [['geographic_location_id' => null], [2, 3]], [['geographic_location_id' => $country['id']], [0, 1]],
        [['developer_id' => null, 'geographic_location_id' => null], [3]],
        [['developer_id' => $developer['id'], 'geographic_location_id' => $country['id']], [0]],
    ];
    foreach ($matrix as [$filter, $indices]) {
        $expected = array_map(static fn ($index) => $projects[$index]['id'], $indices);
        foreach ([[], ['search' => 'MATRIX']] as $search) {
            bf014RepoEqual(bf014RepoIds($development->listProjects($filter + $search)), $expected, 'Project nullable matrix ' . json_encode($filter + $search));
        }
    }
    $moved = $development->updateProject($projects[0]['id'], ['developer_id' => $developer2['id'], 'geographic_location_id' => null]);
    bf014RepoEqual($moved['developer_id'], $developer2['id'], 'Project reassignment');
    bf014RepoEqual($moved['geographic_location_id'], null, 'Project null reassignment');
    bf014RepoThrows(fn () => $development->deleteDeveloper($developer['id']), PDOException::class, 'Referenced developer', 1451);
    foreach (['developer_id', 'geographic_location_id'] as $field) {
        bf014RepoThrows(fn () => $development->updateProject($projects[0]['id'], [$field => $missing]), PDOException::class, 'Project FK ' . $field, 1452);
    }
    $p1 = $projects[0]['id'];
    $p2 = $projects[1]['id'];
    $phase = $development->createProjectPhase($p1, $data('PHASE%_!', ['sort_order' => 2]));
    $phase2 = $development->createProjectPhase($p1, $data('PHASE_PLAIN', ['sort_order' => 1]));
    $phaseOther = $development->createProjectPhase($p2, $data('PHASE%_!'));
    bf014RepoEqual($development->findProjectPhaseById($phase['id']), $phase, 'Phase ID');
    bf014RepoEqual($development->findProjectPhaseByUlid($phase['ulid']), $phase, 'Phase ULID');
    bf014RepoEqual($development->findProjectPhaseByCode($p1, $phase['code']), $phase, 'Phase scoped code');
    bf014RepoEqual($development->findProjectPhaseByCode($p2, $phase['code']), $phaseOther, 'Phase repeated code other project');
    bf014RepoEqual($development->projectPhaseExistsByCode($p1, $phase['code']), true, 'Phase exists');
    bf014RepoEqual($development->projectPhaseExistsByCode($p2, 'PHASE_PLAIN'), false, 'Phase existence scope');
    bf014RepoEqual(bf014RepoIds($development->listProjectPhases($p1)), [$phase2['id'], $phase['id']], 'Phase order/scope');
    bf014RepoEqual(bf014RepoIds($development->listProjectPhases($p1, ['search' => 'PHASE', 'limit' => 1, 'offset' => 1])), [$phase['id']], 'Phase search pagination');
    bf014RepoEqual(bf014RepoIds($development->listProjectPhases($p1, ['search' => '%_!'])), [$phase['id']], 'Phase literal search');
    bf014RepoEqual($development->updateProjectPhase($p2, $phase['id'], ['name_en' => 'bad']), null, 'Phase foreign update');
    bf014RepoEqual($development->updateProjectPhase($p2, $phase['id'], []), null, 'Phase foreign reread');
    bf014RepoEqual($development->deleteProjectPhase($p2, $phase['id']), false, 'Phase foreign delete');
    bf014RepoEqual($development->findProjectPhaseById($phase['id']), $phase, 'Phase unchanged after foreign operations');
    bf014RepoEqual($development->updateProjectPhase($p1, $phase['id'], ['name_en' => 'Changed'])['name_en'], 'Changed', 'Phase update');
    bf014RepoThrows(fn () => $development->createProjectPhase($p1, $data($phase['code'])), PDOException::class, 'Phase duplicate scoped code', 1062);
    bf014RepoEqual($development->deleteProjectPhase($p1, $phase['id']), true, 'Phase delete');
    echo "Development / 14 project filter cases / phase scope: PASS\n";

    foreach ([[$catalog, 'findCategoryById'], [$measurements, 'findMeasurementDefinitionById'], [$attributes, 'findAttributeDefinitionById'],
        [$configurations, 'findConfigurationById'], [$geography, 'findGeographicLocationById'], [$development, 'findDeveloperById']] as [$repo, $method]) {
        foreach ([0, '0', -1, '1.5', 'abc', (string) PHP_INT_MAX . '0'] as $id) {
            $before = bf014RepoExecutions($pdo);
            bf014RepoThrows(fn () => $repo->$method($id), InvalidArgumentException::class, $method . ' invalid ID');
            bf014RepoEqual(bf014RepoExecutions($pdo), $before, 'Invalid ID no SQL');
        }
    }
    foreach (['unit_type_id', 'version_number', 'ulid', 'provenance', 'created_by_user_id', 'active_unit_type_guard'] as $field) {
        $before = bf014RepoExecutions($pdo);
        bf014RepoThrows(fn () => $configurations->updateConfigurationVersion($c1['id'], [$field => 1]), InvalidArgumentException::class, 'Configuration immutable ' . $field);
        bf014RepoEqual(bf014RepoExecutions($pdo), $before, 'Rejected config no SQL');
    }
    bf014RepoThrows(fn () => $development->updateProjectPhase($p1, $phase2['id'], ['project_id' => $p2]), InvalidArgumentException::class, 'Phase immutable parent');
    foreach ([['Measurement', 'measurement_definition_id'], ['Attribute', 'attribute_definition_id']] as [$stem, $field]) {
        foreach (['configuration_version_id', $field, 'created_by_user_id', 'unknown'] as $immutable) {
            $before = bf014RepoExecutions($pdo);
            bf014RepoThrows(fn () => $configurations->{'update' . $stem . 'Rule'}($c1['id'], $rules[$stem][0]['id'], [$immutable => 1]), InvalidArgumentException::class, 'Rule immutable ' . $immutable);
            bf014RepoEqual(bf014RepoExecutions($pdo), $before, 'Rejected rule no SQL');
        }
    }
    bf014RepoEqual($catalog->findUnitTypeForUpdate($unit['id'])['id'], $unit['id'], 'Unit Type lock read');
    bf014RepoEqual($pdo->inTransaction(), false, 'Lock read does not auto-begin transaction');
    bf014RepoEqual($catalog->findCategoryById($category['id']), $category, 'QueryBuilder clean after lock');
    echo "Identifier / allowlist / container / QueryBuilder recovery: PASS\n";
} finally {
    $cleanup();
}
echo "BF014.2D ACCEPTANCE: PASS\n";
