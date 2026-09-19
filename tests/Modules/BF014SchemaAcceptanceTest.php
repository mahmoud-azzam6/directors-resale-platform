<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Symfony\Component\Uid\Ulid;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function bf014Assert(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
}

function bf014Reject(callable $operation, int $errorNumber, string $message): void
{
    try { $operation(); }
    catch (PDOException $exception) {
        bf014Assert((int) ($exception->errorInfo[1] ?? 0) === $errorNumber, $message . ': unexpected database error ' . $exception->getMessage());
        return;
    }
    throw new RuntimeException($message . ': invalid operation succeeded.');
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$connection = $config['connections']['mysql'];
$developmentDatabase = (string) $connection['database'];
$databaseName = 'directors_resale_platform_bf014_test_' . getmypid();
$safeName = static fn (string $name): bool => preg_match('/^directors_resale_platform_bf014_test_[0-9]+$/D', $name) === 1;
bf014Assert($safeName($databaseName) && $databaseName !== $developmentDatabase, 'Unsafe test database name.');
$pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), (string) $connection['username'], (string) $connection['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$created = false;
$cleanup = static function () use ($pdo, $databaseName, $developmentDatabase, $safeName, &$created): void {
    if (! $created) { return; }
    bf014Assert($safeName($databaseName) && $databaseName !== $developmentDatabase, 'Unsafe cleanup refused.');
    $pdo->exec("DROP DATABASE `{$databaseName}`");
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = ?');
    $statement->execute([$databaseName]);
    bf014Assert((int) $statement->fetchColumn() === 0, 'Test database cleanup failed.');
    $created = false;
    echo "remaining_bf014_test_database=0\n";
};
register_shutdown_function($cleanup);

try {
    $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    bf014Assert(str_starts_with($version, '10.4.32-MariaDB'), 'Acceptance requires MariaDB 10.4.32.');
    $pdo->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $pdo->exec("USE `{$databaseName}`");
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    $pdo->exec('SET SESSION foreign_key_checks = 1, check_constraint_checks = 1');

    $tables = ['property_categories', 'unit_types', 'unit_type_configuration_versions', 'measurement_definitions', 'unit_type_measurement_rules', 'attribute_definitions', 'attribute_options', 'unit_type_attribute_rules', 'geographic_locations', 'developers', 'projects', 'project_phases', 'property_catalog_seed_versions'];
    $migrations = glob($root . '/database/migrations/*.sql') ?: [];
    sort($migrations, SORT_STRING);
    bf014Assert(count($migrations) === 32, 'Expected exactly migrations 001-032.');
    foreach ($migrations as $index => $path) {
        bf014Assert((int) substr(basename($path), 0, 3) === $index + 1, 'Migration sequence gap.');
        $sql = file_get_contents($path);
        bf014Assert(is_string($sql) && trim($sql) !== '', 'Unreadable migration.');
        if ($index >= 17 && $index < 30) {
            bf014Assert(basename($path) === sprintf('%03d_create_%s_table.sql', $index + 1, $tables[$index - 17]), 'Unexpected BF014 migration.');
            bf014Assert(substr_count($sql, 'CREATE TABLE ') === 1 && ! preg_match('/\b(INSERT|UPDATE|DELETE|TRIGGER|PROCEDURE)\s+(INTO|FROM|TABLE|ON)\b/i', $sql), 'Migration contains unexpected data/logic statements.');
        }
        $pdo->exec($sql);
    }
    echo "MariaDB {$version}: migrations 001-032 applied.\n";
    foreach ($tables as $table) {
        bf014Assert((int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn() === 0, "{$table} was seeded by migrations.");
    }
    bf014Assert((int) $pdo->query('SELECT COUNT(*) FROM permissions')->fetchColumn() === 32, 'Permission migration did not add the BF014.4 capabilities.');
    echo "All 13 new tables contain zero rows after migration; permissions are 32.\n";

    $names = "'" . implode("','", $tables) . "'";
    $fks = $pdo->query("SELECT CONSTRAINT_NAME, DELETE_RULE, UPDATE_RULE FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND table_name IN ({$names})")->fetchAll();
    bf014Assert(count($fks) === 36, 'Expected 36 BF014 foreign keys.');
    foreach ($fks as $fk) {
        bf014Assert($fk['DELETE_RULE'] === 'RESTRICT' && $fk['UPDATE_RULE'] === 'RESTRICT', 'Non-RESTRICT foreign key.');
    }
    $guards = $pdo->query("SELECT COLUMN_NAME, EXTRA FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name IN ({$names}) AND EXTRA LIKE '%GENERATED%'")->fetchAll();
    bf014Assert(count($guards) === 3, 'Expected three generated guards.');
    foreach ($guards as $guard) { bf014Assert(str_contains($guard['EXTRA'], 'STORED GENERATED'), 'Guard is not PERSISTENT.'); }
    bf014Assert((int) $pdo->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'unit_type_configuration_versions' AND index_name = 'uq_unit_type_configs_active_guard'")->fetchColumn() === 1, 'ACTIVE guard changed.');
    bf014Assert((int) $pdo->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'unit_type_configuration_versions' AND index_name = 'uq_unit_type_configs_draft_guard'")->fetchColumn() === 1, 'DRAFT guard missing.');

    // Disposable constraint fixtures only: these are not baseline seeds.
    $insert = static function (string $table, array $values) use ($pdo): int {
        $columns = implode(',', array_keys($values));
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $pdo->prepare("INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})")->execute(array_values($values));
        return (int) $pdo->lastInsertId();
    };
    $serial = 0;
    $catalog = static function (array $extra = []) use (&$serial): array {
        return array_replace(['ulid' => (string) new Ulid(), 'code' => 'TEST_' . ++$serial, 'name_ar' => 'Ø§Ø®ØªØ¨Ø§Ø±', 'name_en' => 'Test', 'status' => 'active', 'provenance' => 'SYSTEM_ADMIN'], $extra);
    };
    $cat = $insert('property_categories', $catalog());
    $unit = $insert('unit_types', $catalog(['property_category_id' => $cat]));
    $configRow = static fn (int $number, string $status = 'draft'): array => ['ulid' => (string) new Ulid(), 'unit_type_id' => $unit, 'version_number' => $number, 'status' => $status, 'provenance' => 'SYSTEM_SEED'];
    $active = $insert('unit_type_configuration_versions', $configRow(1, 'active'));
    $draft = $insert('unit_type_configuration_versions', $configRow(2));
    bf014Reject(fn () => $insert('unit_type_configuration_versions', $configRow(3)), 1062, 'Multiple draft configurations');
    $pdo->exec("UPDATE unit_type_configuration_versions SET status='historical' WHERE id={$draft}");
    $insert('unit_type_configuration_versions', $configRow(3));
    bf014Reject(fn () => $insert('unit_type_configuration_versions', $configRow(4, 'active')), 1062, 'Multiple active configurations');
    bf014Reject(fn () => $pdo->exec("UPDATE unit_type_configuration_versions SET status='active' WHERE id={$draft}"), 1062, 'Active guard on update');
    bf014Reject(fn () => $insert('unit_type_configuration_versions', $configRow(1)), 1062, 'Duplicate version number');
    bf014Reject(fn () => $insert('unit_type_configuration_versions', $configRow(0)), 4025, 'Zero version number');
    bf014Reject(fn () => $insert('unit_type_configuration_versions', $configRow(4, 'invalid')), 4025, 'Configuration status');
    $pdo->beginTransaction();
    $pdo->exec("UPDATE unit_type_configuration_versions SET status='historical' WHERE id={$active}");
    $pdo->exec("UPDATE unit_type_configuration_versions SET status='active' WHERE id={$draft}");
    $pdo->rollBack();
    bf014Assert((int) $pdo->query("SELECT active_unit_type_guard FROM unit_type_configuration_versions WHERE id={$active}")->fetchColumn() === $unit, 'Guard rollback failed.');
    bf014Assert((int) $pdo->query("SELECT last_allocated_configuration_version FROM unit_types WHERE id={$unit}")->fetchColumn() === 0, 'New Unit Type allocation default failed.');
    bf014Reject(fn () => $pdo->exec("UPDATE unit_types SET last_allocated_configuration_version=-1 WHERE id={$unit}"), 1264, 'Negative allocation state');

    $measurement = $insert('measurement_definitions', $catalog(['default_unit_code' => 'SQM']));
    $measurement2 = $insert('measurement_definitions', $catalog(['default_unit_code' => 'SQM']));
    bf014Reject(fn () => $insert('measurement_definitions', $catalog(['default_unit_code' => 'SQFT'])), 4025, 'V1 unit CHECK');
    $rule = ['configuration_version_id' => $active, 'measurement_definition_id' => $measurement, 'requirement' => 'REQUIRED', 'is_primary' => 1];
    $primary = $insert('unit_type_measurement_rules', $rule);
    bf014Reject(fn () => $insert('unit_type_measurement_rules', array_replace($rule, ['measurement_definition_id' => $measurement2])), 1062, 'Multiple primary measurements');
    bf014Reject(fn () => $insert('unit_type_measurement_rules', array_replace($rule, ['is_primary' => 0])), 1062, 'Duplicate measurement rule');
    bf014Reject(fn () => $insert('unit_type_measurement_rules', array_replace($rule, ['configuration_version_id' => $draft, 'requirement' => 'OPTIONAL'])), 4025, 'Primary OPTIONAL');
    bf014Reject(fn () => $insert('unit_type_measurement_rules', array_replace($rule, ['configuration_version_id' => $draft, 'is_primary' => 2])), 4025, 'Invalid primary flag');
    $optional = $insert('unit_type_measurement_rules', array_replace($rule, ['measurement_definition_id' => $measurement2, 'requirement' => 'OPTIONAL', 'is_primary' => 0]));
    bf014Reject(fn () => $pdo->exec("UPDATE unit_type_measurement_rules SET is_primary=1, requirement='REQUIRED' WHERE id={$optional}"), 1062, 'Primary guard on update');
    bf014Reject(fn () => $pdo->exec("UPDATE unit_type_measurement_rules SET requirement='OPTIONAL' WHERE id={$primary}"), 4025, 'Primary requirement on update');
    bf014Reject(fn () => $insert('unit_type_measurement_rules', array_replace($rule, ['configuration_version_id' => $draft, 'requirement' => 'INVALID', 'is_primary' => 0])), 4025, 'Measurement requirement');

    $attributes = [];
    foreach (['INTEGER', 'DECIMAL', 'BOOLEAN', 'TEXT', 'ENUM', 'DATE'] as $type) {
        $attributes[$type] = $insert('attribute_definitions', $catalog(['data_type' => $type, 'text_max_length' => $type === 'TEXT' ? 100 : null]));
    }
    bf014Reject(fn () => $insert('attribute_definitions', $catalog(['data_type' => 'INVALID'])), 4025, 'Attribute type');
    bf014Reject(fn () => $insert('attribute_definitions', $catalog(['data_type' => 'TEXT', 'text_max_length' => 0])), 4025, 'Text length zero');
    bf014Reject(fn () => $insert('attribute_definitions', $catalog(['data_type' => 'INTEGER', 'text_max_length' => 10])), 4025, 'Text limit on INTEGER');
    $option = $insert('attribute_options', $catalog(['attribute_definition_id' => $attributes['ENUM'], 'code' => 'OPTION']));
    bf014Reject(fn () => $insert('attribute_options', $catalog(['attribute_definition_id' => $attributes['ENUM'], 'code' => 'OPTION'])), 1062, 'Duplicate option code');
    $enum2 = $insert('attribute_definitions', $catalog(['data_type' => 'ENUM']));
    $insert('attribute_options', $catalog(['attribute_definition_id' => $enum2, 'code' => 'OPTION']));
    $attributeRule = ['configuration_version_id' => $active, 'attribute_definition_id' => $attributes['INTEGER'], 'requirement' => 'REQUIRED'];
    $insert('unit_type_attribute_rules', $attributeRule);
    bf014Reject(fn () => $insert('unit_type_attribute_rules', $attributeRule), 1062, 'Duplicate attribute rule');
    bf014Reject(fn () => $insert('unit_type_attribute_rules', array_replace($attributeRule, ['configuration_version_id' => $draft, 'requirement' => 'INVALID'])), 4025, 'Attribute requirement');
    $insert('unit_type_attribute_rules', array_replace($attributeRule, ['configuration_version_id' => $draft, 'requirement' => 'OPTIONAL']));

    $country = $insert('geographic_locations', $catalog(['code' => 'TEST_COUNTRY', 'type' => 'COUNTRY']));
    $city = $insert('geographic_locations', $catalog(['parent_id' => $country, 'type' => 'CITY']));
    $insert('geographic_locations', $catalog(['parent_id' => $city, 'type' => 'DISTRICT']));
    bf014Reject(fn () => $insert('geographic_locations', $catalog(['code' => 'TEST_COUNTRY', 'type' => 'COUNTRY'])), 1062, 'Global geography code');
    bf014Reject(fn () => $insert('geographic_locations', $catalog(['type' => 'CITY'])), 4025, 'Non-country root');
    bf014Reject(fn () => $insert('geographic_locations', $catalog(['type' => 'COUNTRY', 'parent_id' => $country])), 4025, 'Country with parent');
    bf014Reject(fn () => $insert('geographic_locations', $catalog(['type' => 'INVALID', 'parent_id' => $country])), 4025, 'Geography type');
    $developer = $insert('developers', $catalog());
    $project = $insert('projects', $catalog(['developer_id' => $developer, 'geographic_location_id' => $city]));
    $project2 = $insert('projects', $catalog());
    $phase = $insert('project_phases', $catalog(['project_id' => $project, 'code' => 'PHASE']));
    $insert('project_phases', $catalog(['project_id' => $project2, 'code' => 'PHASE']));
    bf014Reject(fn () => $insert('project_phases', $catalog(['project_id' => $project, 'code' => 'PHASE'])), 1062, 'Project-scoped phase code');

    $fixtures = ['property_categories' => $cat, 'unit_types' => $unit, 'unit_type_configuration_versions' => $active, 'measurement_definitions' => $measurement, 'unit_type_measurement_rules' => $primary, 'attribute_definitions' => $attributes['INTEGER'], 'attribute_options' => $option, 'unit_type_attribute_rules' => 1, 'geographic_locations' => $country, 'developers' => $developer, 'projects' => $project, 'project_phases' => $phase];
    foreach ($fixtures as $table => $id) {
        bf014Assert((int) $pdo->query("SELECT COUNT(*) FROM `{$table}` WHERE id={$id} AND created_by_user_id IS NULL AND updated_by_user_id IS NULL")->fetchColumn() === 1, "NULL actors failed: {$table}");
        foreach (['created_by_user_id', 'updated_by_user_id'] as $actor) {
            bf014Reject(fn () => $pdo->exec("UPDATE `{$table}` SET {$actor}=999999 WHERE id={$id}"), 1452, "Invalid actor: {$table}.{$actor}");
        }
        if (in_array($table, ['unit_type_configuration_versions', 'unit_type_measurement_rules', 'unit_type_attribute_rules'], true)) { continue; }
        foreach (['status' => 'invalid', 'provenance' => 'INVALID', 'code' => ' ', 'name_ar' => ' ', 'name_en' => ' '] as $column => $value) {
            bf014Reject(fn () => $pdo->prepare("UPDATE `{$table}` SET {$column}=? WHERE id=?")->execute([$value, $id]), 4025, "Catalog CHECK: {$table}.{$column}");
        }
    }
    bf014Reject(fn () => $pdo->exec("UPDATE unit_type_configuration_versions SET provenance='INVALID' WHERE id={$active}"), 4025, 'Configuration provenance');

    foreach (['property_categories' => $cat, 'unit_types' => $unit, 'unit_type_configuration_versions' => $active, 'measurement_definitions' => $measurement, 'attribute_definitions' => $attributes['ENUM'], 'geographic_locations' => $country, 'developers' => $developer, 'projects' => $project] as $table => $id) {
        bf014Reject(fn () => $pdo->exec("DELETE FROM `{$table}` WHERE id={$id}"), 1451, "Restricted delete: {$table}");
        bf014Reject(fn () => $pdo->exec("UPDATE `{$table}` SET id=999999 WHERE id={$id}"), 1451, "Restricted key update: {$table}");
    }
    bf014Reject(fn () => $pdo->exec("UPDATE projects SET developer_id=999999 WHERE id={$project}"), 1452, 'Project Developer FK');
    bf014Reject(fn () => $pdo->exec("UPDATE projects SET geographic_location_id=999999 WHERE id={$project}"), 1452, 'Project geography FK');
    bf014Reject(fn () => $pdo->exec("UPDATE project_phases SET project_id=999999 WHERE id={$phase}"), 1452, 'Phase Project FK');

    $ledger = ['seed_key' => 'test_package', 'checksum' => hash('sha256', 'test fixture')];
    $ledgerId = $insert('property_catalog_seed_versions', $ledger);
    bf014Assert($pdo->query("SELECT applied_by_user_id FROM property_catalog_seed_versions WHERE id={$ledgerId}")->fetchColumn() === null, 'NULL seed actor');
    bf014Reject(fn () => $insert('property_catalog_seed_versions', $ledger), 1062, 'Duplicate seed key');
    bf014Reject(fn () => $insert('property_catalog_seed_versions', array_replace($ledger, ['seed_key' => ' '])), 4025, 'Blank seed key');
    foreach (['short', str_repeat('z', 64), str_repeat('0', 63) . "\n"] as $checksum) {
        bf014Reject(fn () => $insert('property_catalog_seed_versions', ['seed_key' => 'invalid_checksum', 'checksum' => $checksum]), 4025, 'Checksum shape');
    }
    bf014Reject(fn () => $pdo->exec("UPDATE property_catalog_seed_versions SET applied_by_user_id=999999 WHERE id={$ledgerId}"), 1452, 'Invalid seed actor');
    echo "BF014 schema acceptance passed: CHECKs, unique guards, duplicate rules/versions/codes, FK RESTRICT, NULL/invalid actors, flexible geography, seed ledger.\n";
} finally {
    $cleanup();
}
