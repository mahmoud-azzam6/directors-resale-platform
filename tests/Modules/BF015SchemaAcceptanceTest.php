<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function bf015Assert(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function bf015Reject(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (PDOException) {
        return;
    }

    throw new RuntimeException($message . ': invalid operation succeeded.');
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$connection = $config['connections']['mysql'];
$name = 'directors_resale_platform_bf015_schema_' . getmypid();
bf015Assert(preg_match('/^directors_resale_platform_bf015_schema_[0-9]+$/D', $name) === 1 && $name !== $connection['database'], 'Unsafe temporary database name.');

$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;

try {
    bf015Assert(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB is required.');
    $server->exec("CREATE DATABASE $name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $connection['host'], $connection['port'], $name), $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $migrations = glob($root . '/database/migrations/*.sql') ?: [];
    sort($migrations, SORT_STRING);
    bf015Assert(count($migrations) === 33, 'Expected migrations 001-033.');
    foreach ($migrations as $migration) {
        $pdo->exec((string) file_get_contents($migration));
    }

    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('organization_property_profiles', 'property_measurements', 'property_attribute_values') ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
    bf015Assert($tables === ['organization_property_profiles', 'property_attribute_values', 'property_measurements'], 'BF015 tables were not created.');

    $pdo->exec("INSERT INTO organizations(id,name,code,organization_type,status) VALUES(1,'System','SYS','system','active')");
    $pdo->exec("INSERT INTO positions(id,organization_id,name,code,status) VALUES(1,1,'Profile','PROFILE','active')");
    $pdo->exec("INSERT INTO users(id,organization_id,full_name,email,status,password_hash,position_id) VALUES(1,1,'Profile User','profile@example.test','active','hash',1)");
    $pdo->exec("INSERT INTO organization_properties(id,ulid,organization_id,property_label,status,created_by_user_id,updated_by_user_id) VALUES(1,'01HF0150000000000000000001',1,'Shell','active',1,1),(2,'01HF0150000000000000000002',1,'Partial','active',1,1)");
    $pdo->exec("INSERT INTO property_categories(id,ulid,code,name_ar,name_en,status,provenance) VALUES(1,'01HF0150000000000000000003','RESIDENTIAL','Residential','Residential','active','SYSTEM_ADMIN')");
    $pdo->exec("INSERT INTO unit_types(id,ulid,property_category_id,code,name_ar,name_en,status,provenance) VALUES(1,'01HF0150000000000000000004',1,'APARTMENT','Apartment','Apartment','active','SYSTEM_ADMIN')");
    $pdo->exec("INSERT INTO unit_type_configuration_versions(id,ulid,unit_type_id,version_number,status,provenance) VALUES(1,'01HF0150000000000000000005',1,1,'active','SYSTEM_ADMIN')");
    $pdo->exec("INSERT INTO geographic_locations(id,ulid,parent_id,code,name_ar,name_en,status,provenance,type) VALUES(1,'01HF0150000000000000000006',NULL,'EGYPT','Egypt','Egypt','active','SYSTEM_ADMIN','COUNTRY')");
    $pdo->exec("INSERT INTO developers(id,ulid,code,name_ar,name_en,status,provenance) VALUES(1,'01HF0150000000000000000007','DEV','Developer','Developer','active','SYSTEM_ADMIN')");
    $pdo->exec("INSERT INTO projects(id,ulid,developer_id,code,name_ar,name_en,status,provenance) VALUES(1,'01HF0150000000000000000008',1,'PROJECT','Project','Project','active','SYSTEM_ADMIN')");
    $pdo->exec("INSERT INTO project_phases(id,ulid,project_id,code,name_ar,name_en,status,provenance) VALUES(1,'01HF0150000000000000000009',1,'PHASE','Phase','Phase','active','SYSTEM_ADMIN')");
    $pdo->exec("INSERT INTO measurement_definitions(id,ulid,code,name_ar,name_en,status,provenance,default_unit_code) VALUES(1,'01HF0150000000000000000010','AREA','Area','Area','active','SYSTEM_ADMIN','SQM')");
    $pdo->exec("INSERT INTO attribute_definitions(id,ulid,code,name_ar,name_en,status,provenance,data_type) VALUES(1,'01HF0150000000000000000011','BEDROOMS','Bedrooms','Bedrooms','active','SYSTEM_ADMIN','INTEGER'),(2,'01HF0150000000000000000012','VIEW','View','View','active','SYSTEM_ADMIN','ENUM')");
    $pdo->exec("INSERT INTO attribute_options(id,ulid,attribute_definition_id,code,name_ar,name_en,status,provenance) VALUES(1,'01HF0150000000000000000013',2,'SEA','Sea','Sea','active','SYSTEM_ADMIN')");

    $pdo->exec("INSERT INTO organization_property_profiles(organization_property_id,property_category_id,unit_type_id,accepted_configuration_version_id,geographic_location_id,development_reference_type,project_phase_id,created_by_user_id,updated_by_user_id) VALUES(1,1,1,1,1,'PHASE',1,1,1)");
    bf015Assert((int) $pdo->query('SELECT revision FROM organization_property_profiles WHERE organization_property_id=1')->fetchColumn() === 1, 'Profile revision must default to one.');
    $pdo->exec("INSERT INTO organization_property_profiles(organization_property_id,property_category_id,created_by_user_id,updated_by_user_id) VALUES(2,1,1,1)");
    bf015Reject(fn () => $pdo->exec("INSERT INTO organization_property_profiles(organization_property_id,created_by_user_id,updated_by_user_id) VALUES(1,1,1)"), 'Profile one-to-one uniqueness');
    bf015Reject(fn () => $pdo->exec("INSERT INTO organization_property_profiles(organization_property_id,created_by_user_id,updated_by_user_id) VALUES(999,1,1)"), 'Profile property FK');
    bf015Reject(fn () => $pdo->exec("UPDATE organization_property_profiles SET revision=0 WHERE organization_property_id=2"), 'Profile revision CHECK');
    bf015Reject(fn () => $pdo->exec("UPDATE organization_property_profiles SET development_reference_type='PROJECT',project_phase_id=1 WHERE organization_property_id=2"), 'Development reference CHECK');

    $pdo->exec("INSERT INTO property_measurements(organization_property_id,measurement_definition_id,value_decimal,unit_code,created_by_user_id,updated_by_user_id) VALUES(1,1,125.5000,'SQM',1,1)");
    bf015Reject(fn () => $pdo->exec("INSERT INTO property_measurements(organization_property_id,measurement_definition_id,value_decimal,unit_code,created_by_user_id,updated_by_user_id) VALUES(1,1,20,'SQM',1,1)"), 'Measurement uniqueness');
    bf015Reject(fn () => $pdo->exec("INSERT INTO property_measurements(organization_property_id,measurement_definition_id,value_decimal,unit_code,created_by_user_id,updated_by_user_id) VALUES(2,1,0,'SQM',1,1)"), 'Positive measurement CHECK');
    bf015Reject(fn () => $pdo->exec("INSERT INTO property_measurements(organization_property_id,measurement_definition_id,value_decimal,unit_code,created_by_user_id,updated_by_user_id) VALUES(1,999,20,'SQM',1,1)"), 'Measurement Definition FK');

    $pdo->exec("INSERT INTO property_attribute_values(organization_property_id,attribute_definition_id,data_type,value_integer,created_by_user_id,updated_by_user_id) VALUES(1,1,'INTEGER',3,1,1)");
    $pdo->exec("INSERT INTO property_attribute_values(organization_property_id,attribute_definition_id,data_type,attribute_option_id,created_by_user_id,updated_by_user_id) VALUES(1,2,'ENUM',1,1,1)");
    bf015Reject(fn () => $pdo->exec("INSERT INTO property_attribute_values(organization_property_id,attribute_definition_id,data_type,value_text,created_by_user_id,updated_by_user_id) VALUES(2,1,'INTEGER','wrong',1,1)"), 'Typed value CHECK');
    bf015Reject(fn () => $pdo->exec("INSERT INTO property_attribute_values(organization_property_id,attribute_definition_id,data_type,attribute_option_id,created_by_user_id,updated_by_user_id) VALUES(2,1,'ENUM',999,1,1)"), 'ENUM Option FK');
    bf015Reject(fn () => $pdo->exec("DELETE FROM measurement_definitions WHERE id=1"), 'Catalog delete must not cascade');
    bf015Reject(fn () => $pdo->exec("DELETE FROM organization_property_profiles WHERE organization_property_id=1"), 'Profile deletion must preserve values');

    foreach (['organization_property_profiles', 'property_measurements', 'property_attribute_values'] as $table) {
        bf015Assert((int) $pdo->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='$table'")->fetchColumn() > 1, $table . ' indexes are missing.');
    }

    echo "BF015 schema MariaDB acceptance: PASS\n";
} finally {
    if ($created) {
        $server->exec("DROP DATABASE $name");
    }
}
