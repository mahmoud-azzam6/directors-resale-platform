<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\PropertyCatalogRepository;
use App\Modules\Property\Repositories\MeasurementDefinitionRepository;
use App\Modules\Property\Repositories\AttributeDefinitionRepository;
use App\Modules\Property\Repositories\UnitTypeConfigurationRepository;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Modules\Property\Services\UnitTypeConfigurationService;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function e1Assert(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

function e1Error(callable $operation, string $code): void
{
    try { $operation(); }
    catch (ValidationException $exception) {
        e1Assert(isset($exception->errors()[$code]), 'Expected ' . $code . ', got ' . json_encode($exception->errors()));
        return;
    }
    throw new RuntimeException('Expected ' . $code);
}

function e1Data(string $code, array $extra = []): array
{
    return $extra + ['code' => $code, 'name_ar' => trim($code), 'name_en' => trim($code)];
}

/** Compare persisted state, including rules, allocation and audit fields. */
function e1Snapshot(PDO $pdo): array
{
    $snapshot = [];
    foreach (['property_categories', 'unit_types', 'measurement_definitions', 'attribute_definitions', 'attribute_options', 'unit_type_configuration_versions', 'unit_type_measurement_rules', 'unit_type_attribute_rules'] as $table) {
        $snapshot[$table] = $pdo->query("SELECT * FROM `$table` ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    }
    return $snapshot;
}

function e1Unchanged(PDO $pdo, callable $operation, string $code): void
{
    $before = e1Snapshot($pdo);
    e1Error($operation, $code);
    e1Assert(e1Snapshot($pdo) === $before, $code . ' left partial persisted state');
    e1Assert(!$pdo->inTransaction(), $code . ' left an open transaction');
}

function e1InjectedFailure(PDO $pdo, string $trigger, string $sql, callable $operation): void
{
    $before = e1Snapshot($pdo);
    $pdo->exec($sql);
    try {
        try { $operation(); throw new RuntimeException('Expected injected ' . $trigger); }
        catch (PDOException $exception) {
            e1Assert($exception->getCode() === '45000' && str_contains($exception->getMessage(), $trigger), 'Unexpected database error: ' . $exception->getMessage());
        }
    } finally { $pdo->exec('DROP TRIGGER IF EXISTS `' . $trigger . '`'); }
    e1Assert(e1Snapshot($pdo) === $before, $trigger . ' did not roll back the full aggregate/allocation');
    e1Assert(!$pdo->inTransaction(), 'Injected failure left an open transaction');
}

function e1RuleShape(array $rules, array $fields): array
{
    return array_map(fn (array $rule): array => array_intersect_key($rule, array_flip($fields)), $rules);
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$connection = $config['connections']['mysql'];
$name = 'directors_resale_platform_e1_test_' . getmypid();
e1Assert(preg_match('/^directors_resale_platform_e1_test_[0-9]+$/D', $name) === 1 && $name !== $connection['database'], 'Unsafe database');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
e1Assert(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB required');
$created = false; $database = null;
try {
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created = true;
    $config['connections']['mysql']['database'] = $name;
    $database = new DatabaseManager($config); $pdo = $database->connection();
    $migrations = glob($root . '/database/migrations/*.sql') ?: []; sort($migrations);
    foreach ($migrations as $migration) { $pdo->exec(file_get_contents($migration)); }
    $container = new Container(); (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $database);
    $catalog = $container->make(PropertyCatalogService::class);
    $service = $container->make(UnitTypeConfigurationService::class);
    $units = $container->make(PropertyCatalogRepository::class);
    $measurements = $container->make(MeasurementDefinitionRepository::class);
    $attributes = $container->make(AttributeDefinitionRepository::class);
    $configs = $container->make(UnitTypeConfigurationRepository::class);

    // A: all canonical fixtures are created through the catalog Service.
    $category = $catalog->createCategory(e1Data(' homes '));
    $unit = $catalog->createUnitType(e1Data(' apartment ', ['property_category_id' => $category['id']]));
    $u = $unit['id'];
    $area = $catalog->createMeasurementDefinition(e1Data(' area ', ['default_unit_code' => 'SQM']));
    $terrace = $catalog->createMeasurementDefinition(e1Data(' terrace ', ['default_unit_code' => 'SQM']));
    $enum = $catalog->createAttributeDefinition(e1Data(' view ', ['data_type' => 'ENUM']));
    $text = $catalog->createAttributeDefinition(e1Data(' note ', ['data_type' => 'TEXT', 'text_max_length' => 100]));
    $city = $catalog->createAttributeOption($enum['id'], e1Data(' city '));
    $sea = $catalog->createAttributeOption($enum['id'], e1Data(' sea '));
    foreach ([[$category, 'HOMES'], [$unit, 'APARTMENT'], [$area, 'AREA'], [$terrace, 'TERRACE'], [$enum, 'VIEW'], [$text, 'NOTE'], [$city, 'CITY'], [$sea, 'SEA']] as [$record, $code]) {
        e1Assert($record['code'] === $code && $record['status'] === 'active', 'Normalized active catalog record');
    }
    e1Assert($units->findCategoryById($category['id']) === $category && $units->findUnitTypeById($u) === $unit, 'Persisted Category/Unit Type reads');
    e1Assert($measurements->findMeasurementDefinitionById($area['id']) === $area && $attributes->findAttributeDefinitionById($enum['id']) === $enum, 'Persisted definition reads');
    e1Assert(count($attributes->listAttributeOptions($enum['id'], ['status' => 'active'])) === 2, 'Persisted active Options');
    foreach (['id', 'ulid', 'code', 'provenance', 'status'] as $field) {
        e1Unchanged($pdo, fn() => $catalog->updateUnitType($u, [$field => $unit[$field]]), 'CATALOG_IDENTITY_IMMUTABLE');
    }
    e1Unchanged($pdo, fn() => $catalog->createCategory(e1Data('homes')), 'CATALOG_CODE_ALREADY_EXISTS');
    e1Unchanged($pdo, fn() => $catalog->deactivateCategory($category['id']), 'CATALOG_ITEM_REFERENCED');
    $catalog->deactivateUnitType($u); $catalog->deactivateCategory($category['id']);
    e1Unchanged($pdo, fn() => $catalog->reactivateUnitType($u), 'PARENT_CATALOG_INACTIVE');
    $catalog->reactivateCategory($category['id']);
    e1Assert($catalog->findUnitType($u)['status'] === 'inactive', 'Category reactivation must not cascade');
    $catalog->reactivateUnitType($u);
    echo "Journey A: PASS\n";

    // B: rule invariants and activation against actual canonical references.
    $v1 = $service->createBlankDraft($u);
    e1Assert($v1['version_number'] === 1 && $units->findUnitTypeById($u)['last_allocated_configuration_version'] === 1, 'Initial allocator version');
    e1Assert($configs->listMeasurementRules($v1['id']) === [] && $configs->listAttributeRules($v1['id']) === [], 'Blank draft structure');
    $mr = $service->addMeasurementRule($u, $v1['id'], ['measurement_definition_id' => $area['id'], 'requirement' => 'REQUIRED', 'is_primary' => true, 'sort_order' => 10]);
    e1Unchanged($pdo, fn() => $service->addMeasurementRule($u, $v1['id'], ['measurement_definition_id' => $area['id'], 'requirement' => 'OPTIONAL']), 'DUPLICATE_CONFIGURATION_RULE');
    e1Unchanged($pdo, fn() => $service->addMeasurementRule($u, $v1['id'], ['measurement_definition_id' => $terrace['id'], 'requirement' => 'REQUIRED', 'is_primary' => true]), 'MULTIPLE_PRIMARY_MEASUREMENTS');
    e1Unchanged($pdo, fn() => $service->addMeasurementRule($u, $v1['id'], ['measurement_definition_id' => $terrace['id'], 'requirement' => 'OPTIONAL', 'is_primary' => true]), 'PRIMARY_MEASUREMENT_MUST_BE_REQUIRED');
    $service->addMeasurementRule($u, $v1['id'], ['measurement_definition_id' => $terrace['id'], 'requirement' => 'OPTIONAL', 'sort_order' => 20]);
    $service->addAttributeRule($u, $v1['id'], ['attribute_definition_id' => $enum['id'], 'requirement' => 'REQUIRED', 'sort_order' => 10]);
    $service->addAttributeRule($u, $v1['id'], ['attribute_definition_id' => $text['id'], 'requirement' => 'OPTIONAL', 'sort_order' => 20]);
    e1Unchanged($pdo, fn() => $service->addAttributeRule($u, $v1['id'], ['attribute_definition_id' => $text['id'], 'requirement' => 'OPTIONAL']), 'DUPLICATE_CONFIGURATION_RULE');
    $catalog->deactivateAttributeOption($enum['id'], $city['id']); $catalog->deactivateAttributeOption($enum['id'], $sea['id']);
    e1Unchanged($pdo, fn() => $service->activate($u, $v1['id']), 'ENUM_HAS_NO_ACTIVE_OPTIONS');
    $catalog->reactivateAttributeOption($enum['id'], $city['id']); $catalog->reactivateAttributeOption($enum['id'], $sea['id']);
    $rules1 = [$configs->listMeasurementRules($v1['id']), $configs->listAttributeRules($v1['id'])];
    $service->activate($u, $v1['id']);
    e1Assert($service->findActive($u)['id'] === $v1['id'] && $service->findDraft($u) === null, 'Draft to active');
    e1Assert([$configs->listMeasurementRules($v1['id']), $configs->listAttributeRules($v1['id'])] === $rules1, 'Activation preserves persisted structure');
    echo "Journey B: PASS\n";

    // C: new rule identities, shared definitions/options, isolated draft edits.
    $canonicalBefore = array_intersect_key(e1Snapshot($pdo), array_flip(['measurement_definitions', 'attribute_definitions', 'attribute_options']));
    $v2 = $service->cloneActiveDraft($u);
    e1Assert($v2['version_number'] === 2 && $v2['ulid'] !== $v1['ulid'] && $v2['id'] !== $v1['id'], 'Clone identity/version');
    foreach ([['listMeasurementRules', ['measurement_definition_id', 'requirement', 'is_primary', 'sort_order']], ['listAttributeRules', ['attribute_definition_id', 'requirement', 'sort_order']]] as [$method, $fields]) {
        $original = $configs->$method($v1['id']); $copy = $configs->$method($v2['id']);
        e1Assert(count($copy) === 2 && e1RuleShape($copy, $fields) === e1RuleShape($original, $fields), 'Clone rule contents');
        e1Assert(array_intersect(array_column($original, 'id'), array_column($copy, 'id')) === [], 'Rules must have version-specific IDs');
    }
    e1Assert(array_intersect_key(e1Snapshot($pdo), $canonicalBefore) === $canonicalBefore, 'Clone duplicated/changed definitions or options');
    $copyMeasurement = $configs->listMeasurementRules($v2['id'])[0];
    $copyAttribute = $configs->listAttributeRules($v2['id'])[1];
    $service->updateMeasurementRule($u, $v2['id'], $copyMeasurement['id'], ['sort_order' => 11]);
    $service->updateAttributeRule($u, $v2['id'], $copyAttribute['id'], ['requirement' => 'REQUIRED']);
    e1Assert([$configs->listMeasurementRules($v1['id']), $configs->listAttributeRules($v1['id'])] === $rules1, 'Draft edits changed source rules');
    $service->activate($u, $v2['id']);
    e1Assert($service->findConfiguration($v1['id'])['status'] === 'historical' && $service->findActive($u)['id'] === $v2['id'], 'Replacement lifecycle');
    $historical = $service->aggregate($v1['id']);
    foreach ([$v1, $v2] as $immutable) {
        $measurementRule = $configs->listMeasurementRules($immutable['id'])[0];
        $attributeRule = $configs->listAttributeRules($immutable['id'])[0];
        e1Unchanged($pdo, fn() => $service->updateMeasurementRule($u, $immutable['id'], $measurementRule['id'], ['sort_order' => 99]), 'CONFIGURATION_STRUCTURE_IMMUTABLE');
        e1Unchanged($pdo, fn() => $service->removeAttributeRule($u, $immutable['id'], $attributeRule['id']), 'CONFIGURATION_STRUCTURE_IMMUTABLE');
        e1Unchanged($pdo, fn() => $service->deleteDraft($u, $immutable['id']), 'CONFIGURATION_NOT_DRAFT');
    }
    echo "Journey C: PASS\n";

    // D: committed deletion consumes the allocated version permanently.
    $v3 = $service->cloneActiveDraft($u);
    e1Unchanged($pdo, fn() => $service->createBlankDraft($u), 'CONFIGURATION_DRAFT_ALREADY_EXISTS');
    e1Unchanged($pdo, fn() => $service->cloneActiveDraft($u), 'CONFIGURATION_DRAFT_ALREADY_EXISTS');
    $service->deleteDraft($u, $v3['id']);
    e1Assert($service->aggregate($v3['id']) === null && $configs->listMeasurementRules($v3['id']) === [] && $configs->listAttributeRules($v3['id']) === [], 'Deleted draft aggregate residue');
    e1Assert($units->findUnitTypeById($u)['last_allocated_configuration_version'] === 3, 'Delete decreased allocator');
    $v4 = $service->createBlankDraft($u);
    e1Assert($v3['version_number'] === 3 && $v4['version_number'] === 4 && $units->findUnitTypeById($u)['last_allocated_configuration_version'] === 4, 'Deleted version reused');
    echo "Journey D: PASS\n";

    // E: active configuration protects shared canonical definitions/options.
    e1Unchanged($pdo, fn() => $catalog->deactivateMeasurementDefinition($area['id']), 'CATALOG_ITEM_REFERENCED');
    e1Unchanged($pdo, fn() => $catalog->deactivateAttributeDefinition($enum['id']), 'CATALOG_ITEM_REFERENCED');
    $catalog->deactivateAttributeOption($enum['id'], $city['id']);
    e1Unchanged($pdo, fn() => $catalog->deactivateAttributeOption($enum['id'], $sea['id']), 'ENUM_ACTIVE_OPTIONS_REQUIRED');
    e1Assert(count($attributes->listAttributeOptions($enum['id'], ['status' => 'active'])) === 1 && $service->findActive($u)['id'] === $v2['id'], 'ENUM availability/lifecycle');
    $activeBefore = $service->aggregate($v2['id']);
    $catalog->deactivateUnitType($u);
    e1Assert($service->aggregate($v2['id']) === $activeBefore && $units->findUnitTypeById($u)['last_allocated_configuration_version'] === 4, 'Unit Type deactivation cascaded');
    $catalog->reactivateUnitType($u);
    $catalog->reactivateAttributeOption($enum['id'], $city['id']);
    // Historical structure is version-owned; resolved Definitions/Options are
    // shared canonical records whose metadata may legitimately change.
    $historicalAfter = $service->aggregate($v1['id']);
    e1Assert($historicalAfter['configuration'] === $historical['configuration'] && $historicalAfter['configuration']['status'] === 'historical', 'Historical configuration row changed');
    e1Assert(array_column($historicalAfter['measurement_rules'], 'rule') === array_column($historical['measurement_rules'], 'rule'), 'Historical Measurement rows/definition relationships changed');
    e1Assert(array_column($historicalAfter['attribute_rules'], 'rule') === array_column($historical['attribute_rules'], 'rule'), 'Historical Attribute rows/definition relationships changed');
    echo "Journey E: PASS\n";

    // F: provenance and reference checks through the Service boundary.
    foreach ([fn() => $catalog->deleteCategory($category['id']), fn() => $catalog->deleteUnitType($u), fn() => $catalog->deleteMeasurementDefinition($area['id']), fn() => $catalog->deleteAttributeDefinition($text['id'])] as $delete) {
        e1Unchanged($pdo, $delete, 'CATALOG_ITEM_REFERENCED');
    }
    foreach (['Category', 'UnitType', 'MeasurementDefinition', 'AttributeDefinition'] as $kind) {
        $extra = match ($kind) {
            'UnitType' => ['property_category_id' => $category['id']],
            'MeasurementDefinition' => ['default_unit_code' => 'SQM'],
            'AttributeDefinition' => ['data_type' => 'BOOLEAN'],
            default => [],
        };
        $create = 'create' . $kind; $delete = 'delete' . $kind; $find = 'find' . $kind;
        $seed = $catalog->$create(e1Data('SEED-' . $kind, $extra + ['provenance' => 'SYSTEM_SEED']));
        e1Unchanged($pdo, fn() => $catalog->$delete($seed['id']), 'SYSTEM_SEED_DELETE_FORBIDDEN');
        $admin = $catalog->$create(e1Data('FREE-' . $kind, $extra));
        e1Assert($catalog->$delete($admin['id']) && $catalog->$find($admin['id']) === null, 'Safe admin deletion: ' . $kind);
    }
    $seedOption = $catalog->createAttributeOption($enum['id'], e1Data('SEED-OPTION', ['provenance' => 'SYSTEM_SEED']));
    e1Unchanged($pdo, fn() => $catalog->deleteAttributeOption($enum['id'], $seedOption['id']), 'SYSTEM_SEED_DELETE_FORBIDDEN');
    echo "Journey F: PASS\n";

    // G: invalidation after rule entry must be caught again at activation.
    $freshMeasurement = $catalog->createMeasurementDefinition(e1Data('fresh-area', ['default_unit_code' => 'SQM']));
    $freshEnum = $catalog->createAttributeDefinition(e1Data('fresh-enum', ['data_type' => 'ENUM']));
    $freshOption = $catalog->createAttributeOption($freshEnum['id'], e1Data('fresh-option'));
    $service->addMeasurementRule($u, $v4['id'], ['measurement_definition_id' => $freshMeasurement['id'], 'requirement' => 'REQUIRED', 'is_primary' => true]);
    $service->addMeasurementRule($u, $v4['id'], ['measurement_definition_id' => $terrace['id'], 'requirement' => 'OPTIONAL']);
    $service->addAttributeRule($u, $v4['id'], ['attribute_definition_id' => $freshEnum['id'], 'requirement' => 'REQUIRED']);
    $service->addAttributeRule($u, $v4['id'], ['attribute_definition_id' => $text['id'], 'requirement' => 'OPTIONAL']);
    $catalog->deactivateMeasurementDefinition($freshMeasurement['id']);
    e1Unchanged($pdo, fn() => $service->activate($u, $v4['id']), 'MEASUREMENT_DEFINITION_INACTIVE');
    $catalog->reactivateMeasurementDefinition($freshMeasurement['id']);
    $catalog->deactivateAttributeOption($freshEnum['id'], $freshOption['id']);
    e1Unchanged($pdo, fn() => $service->activate($u, $v4['id']), 'ENUM_HAS_NO_ACTIVE_OPTIONS');
    e1Assert($service->findDraft($u)['id'] === $v4['id'] && $service->findActive($u)['id'] === $v2['id'], 'Invalid activation changed current lifecycle');
    $catalog->reactivateAttributeOption($freshEnum['id'], $freshOption['id']);
    e1InjectedFailure($pdo, 'e1_fail_activation', "CREATE TRIGGER e1_fail_activation BEFORE UPDATE ON unit_type_configuration_versions FOR EACH ROW BEGIN IF NEW.id = {$v4['id']} AND NEW.status = 'active' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'e1_fail_activation'; END IF; END", fn() => $service->activate($u, $v4['id']));
    $service->activate($u, $v4['id']);
    // The second Attribute insert is reached only after both Measurement rows
    // and one Attribute row have already been inserted in the clone transaction.
    e1InjectedFailure($pdo, 'e1_fail_clone', "CREATE TRIGGER e1_fail_clone BEFORE INSERT ON unit_type_attribute_rules FOR EACH ROW BEGIN IF (SELECT COUNT(*) FROM unit_type_measurement_rules WHERE configuration_version_id = NEW.configuration_version_id) = 2 AND (SELECT COUNT(*) FROM unit_type_attribute_rules WHERE configuration_version_id = NEW.configuration_version_id) = 1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'e1_fail_clone'; END IF; END", fn() => $service->cloneActiveDraft($u));
    e1InjectedFailure($pdo, 'e1_fail_create', "CREATE TRIGGER e1_fail_create BEFORE INSERT ON unit_type_configuration_versions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'e1_fail_create'", fn() => $service->createBlankDraft($u));
    e1Assert($service->findDraft($u) === null && $units->findUnitTypeById($u)['last_allocated_configuration_version'] === 4, 'Failed creation consumed allocation or left draft');
    $v5 = $service->cloneActiveDraft($u);
    e1Assert($v5['version_number'] === 5 && $units->findUnitTypeById($u)['last_allocated_configuration_version'] === 5, 'Rolled-back allocation recovery');
    echo "Journey G: PASS\n";

    $versions = $service->listConfigurations($u);
    $states = [];
    foreach ($versions as $version) { $states[$version['version_number']] = $version['status']; }
    ksort($states);
    e1Assert($states === [1 => 'historical', 2 => 'historical', 4 => 'active', 5 => 'draft'], 'Final version lifecycle: ' . json_encode($states));
    e1Assert($configs->listMeasurementRules($v1['id']) === $rules1[0] && $configs->listAttributeRules($v1['id']) === $rules1[1], 'Historical rules changed');
    e1Assert(count($configs->listMeasurementRules($v5['id'])) === 2 && count($configs->listAttributeRules($v5['id'])) === 2, 'Recovered clone aggregate incomplete');
    e1Assert((int) $pdo->query('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()')->fetchColumn() === 0, 'Temporary trigger residue');
    e1Assert(!$pdo->inTransaction(), 'Transaction residue');
    echo "Versions: 1 -> 2 -> 3 (deleted) -> 4 -> 5; failed allocation attempts rolled back\n";
    echo "Final states: HISTORICAL=2 ACTIVE=1 DRAFT=1; E1 integrated MariaDB acceptance: PASS\n";
} finally {
    if ($database !== null && $database->connection()->inTransaction()) { $database->rollback(); }
    if ($created) { $server->exec("DROP DATABASE `$name`"); }
    $remaining = $server->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
    $remaining->execute([$name]);
    e1Assert((int) $remaining->fetchColumn() === 0, 'Temporary database residue');
}
