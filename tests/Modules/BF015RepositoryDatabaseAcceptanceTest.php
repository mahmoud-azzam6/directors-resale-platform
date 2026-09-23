<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Modules\Property\Repositories\OrganizationPropertyProfileRepository;
use App\Modules\Property\Repositories\PropertyAttributeValueRepository;
use App\Modules\Property\Repositories\PropertyMeasurementRepository;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function bf015RepositoryAssert(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function bf015RepositoryReject(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (PDOException) {
        return;
    }

    throw new RuntimeException($message . ': expected PDO constraint failure.');
}

function bf015Attribute(int $propertyId, int $definitionId, string $dataType, int $userId = 1, mixed $value = null, ?int $optionId = null): array
{
    return [
        'organization_property_id' => $propertyId,
        'attribute_definition_id' => $definitionId,
        'data_type' => $dataType,
        'value_integer' => $dataType === 'INTEGER' ? $value : null,
        'value_decimal' => $dataType === 'DECIMAL' ? $value : null,
        'value_boolean' => $dataType === 'BOOLEAN' ? $value : null,
        'value_text' => $dataType === 'TEXT' ? $value : null,
        'value_date' => $dataType === 'DATE' ? $value : null,
        'attribute_option_id' => $dataType === 'ENUM' ? $optionId : null,
        'created_by_user_id' => $userId,
        'updated_by_user_id' => $userId,
    ];
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$connection = $config['connections']['mysql'];
$name = 'directors_resale_platform_bf015_repositories_' . getmypid();
bf015RepositoryAssert(preg_match('/^directors_resale_platform_bf015_repositories_[0-9]+$/D', $name) === 1 && $name !== $connection['database'], 'Unsafe temporary database name.');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
$db = null;

try {
    bf015RepositoryAssert(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB is required.');
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $config['connections']['mysql']['database'] = $name;
    $db = new DatabaseManager($config);
    $pdo = $db->connection();
    $migrations = glob($root . '/database/migrations/*.sql') ?: [];
    sort($migrations, SORT_STRING);
    bf015RepositoryAssert(count($migrations) === 33, 'Expected migrations 001-033.');
    foreach ($migrations as $migration) {
        $pdo->exec((string) file_get_contents($migration));
    }

    $pdo->exec("INSERT INTO organizations(id,name,code,organization_type,status) VALUES(1,'System','SYS','system','active')");
    $pdo->exec("INSERT INTO positions(id,organization_id,name,code,status) VALUES(1,1,'Profile','PROFILE','active')");
    $pdo->exec("INSERT INTO users(id,organization_id,full_name,email,status,password_hash,position_id) VALUES(1,1,'Profile User','profile@example.test','active','hash',1),(2,1,'Updater','updater@example.test','active','hash',1)");
    $pdo->exec("INSERT INTO organization_properties(id,ulid,organization_id,property_label,status,created_by_user_id,updated_by_user_id) VALUES(1,'01HF0150000000000000000001',1,'One','active',1,1),(2,'01HF0150000000000000000002',1,'Two','active',1,1),(3,'01HF0150000000000000000003',1,'Three','active',1,1)");
    $pdo->exec("INSERT INTO property_categories(id,ulid,code,name_ar,name_en,status,provenance) VALUES(1,'01HF0150000000000000000010','RESIDENTIAL','Residential','Residential','active','SYSTEM_ADMIN')");
    $pdo->exec("INSERT INTO unit_types(id,ulid,property_category_id,code,name_ar,name_en,status,provenance) VALUES(1,'01HF0150000000000000000011',1,'APARTMENT','Apartment','Apartment','active','SYSTEM_ADMIN')");
    $pdo->exec("INSERT INTO unit_type_configuration_versions(id,ulid,unit_type_id,version_number,status,provenance) VALUES(1,'01HF0150000000000000000012',1,1,'active','SYSTEM_ADMIN')");
    $pdo->exec("INSERT INTO geographic_locations(id,ulid,parent_id,code,name_ar,name_en,status,provenance,type) VALUES(1,'01HF0150000000000000000013',NULL,'EGYPT','Egypt','Egypt','active','SYSTEM_ADMIN','COUNTRY')");
    $pdo->exec("INSERT INTO developers(id,ulid,code,name_ar,name_en,status,provenance) VALUES(1,'01HF0150000000000000000014','DEV','Developer','Developer','active','SYSTEM_ADMIN')");
    $pdo->exec("INSERT INTO projects(id,ulid,developer_id,code,name_ar,name_en,status,provenance) VALUES(1,'01HF0150000000000000000015',1,'PROJECT','Project','Project','active','SYSTEM_ADMIN')");
    $pdo->exec("INSERT INTO project_phases(id,ulid,project_id,code,name_ar,name_en,status,provenance) VALUES(1,'01HF0150000000000000000016',1,'PHASE','Phase','Phase','active','SYSTEM_ADMIN')");
    $pdo->exec("INSERT INTO measurement_definitions(id,ulid,code,name_ar,name_en,status,provenance,default_unit_code) VALUES(1,'01HF0150000000000000000020','AREA','Area','Area','active','SYSTEM_ADMIN','SQM'),(2,'01HF0150000000000000000021','PLOT','Plot','Plot','active','SYSTEM_ADMIN','SQM')");
    $pdo->exec("INSERT INTO attribute_definitions(id,ulid,code,name_ar,name_en,status,provenance,data_type) VALUES(1,'01HF0150000000000000000031','INT','Int','Int','active','SYSTEM_ADMIN','INTEGER'),(2,'01HF0150000000000000000032','DEC','Dec','Dec','active','SYSTEM_ADMIN','DECIMAL'),(3,'01HF0150000000000000000033','BOOL','Bool','Bool','active','SYSTEM_ADMIN','BOOLEAN'),(4,'01HF0150000000000000000034','TEXT','Text','Text','active','SYSTEM_ADMIN','TEXT'),(5,'01HF0150000000000000000035','ENUM','Enum','Enum','active','SYSTEM_ADMIN','ENUM'),(6,'01HF0150000000000000000036','DATE','Date','Date','active','SYSTEM_ADMIN','DATE')");
    $pdo->exec("INSERT INTO attribute_options(id,ulid,attribute_definition_id,code,name_ar,name_en,status,provenance) VALUES(1,'01HF0150000000000000000041',5,'SEA','Sea','Sea','active','SYSTEM_ADMIN'),(2,'01HF0150000000000000000042',5,'CITY','City','City','active','SYSTEM_ADMIN')");

    $box = new Container();
    (new AppServiceProvider($box, []))->register();
    $box->instance(DatabaseConnectionInterface::class, $db);
    $profiles = $box->make(OrganizationPropertyProfileRepository::class);
    $measurements = $box->make(PropertyMeasurementRepository::class);
    $attributes = $box->make(PropertyAttributeValueRepository::class);

    bf015RepositoryAssert($profiles->findByOrganizationPropertyId(1) === null, 'Missing profile must map to null.');
    $partial = $profiles->create(['organization_property_id' => 1, 'property_category_id' => 1, 'created_by_user_id' => 1, 'updated_by_user_id' => 1]);
    bf015RepositoryAssert($partial['revision'] === 1 && $partial['unit_type_id'] === null && $partial['development_reference_type'] === null, 'Partial profile nullable mapping failed.');
    $full = $profiles->create(['organization_property_id' => 2, 'property_category_id' => 1, 'unit_type_id' => 1, 'accepted_configuration_version_id' => 1, 'geographic_location_id' => 1, 'development_reference_type' => 'PHASE', 'project_phase_id' => 1, 'created_by_user_id' => 1, 'updated_by_user_id' => 1]);
    bf015RepositoryAssert($profiles->findByOrganizationPropertyId(2) === $full, 'Full profile read failed.');
    bf015RepositoryReject(fn () => $profiles->create(['organization_property_id' => 1, 'created_by_user_id' => 1, 'updated_by_user_id' => 1]), 'Profile one-to-one uniqueness');
    $db->beginTransaction();
    $locked = $profiles->findByOrganizationPropertyIdForUpdate(2);
    bf015RepositoryAssert($locked === $full && $pdo->inTransaction(), 'FOR UPDATE did not return the exact profile in caller transaction.');
    $db->rollback();
    try { $profiles->updateWithExpectedRevision(2, 1, ['organization_property_id' => 2]); throw new RuntimeException('Immutable field accepted.'); } catch (InvalidArgumentException) {}
    $updated = $profiles->updateWithExpectedRevision(2, 1, ['geographic_location_id' => null, 'updated_by_user_id' => 2]);
    bf015RepositoryAssert($updated !== null && $updated['revision'] === 2 && $updated['geographic_location_id'] === null && $updated['updated_by_user_id'] === 2, 'Expected revision update failed.');
    bf015RepositoryAssert($profiles->updateWithExpectedRevision(2, 1, ['updated_by_user_id' => 1]) === null, 'Stale revision update must return null.');
    bf015RepositoryAssert($profiles->findByOrganizationPropertyId(2)['revision'] === 2, 'Stale revision changed profile.');
    $db->beginTransaction();
    $profiles->updateWithExpectedRevision(2, 2, ['updated_by_user_id' => 1]);
    $db->rollback();
    bf015RepositoryAssert($profiles->findByOrganizationPropertyId(2)['revision'] === 2, 'Repository committed caller rollback.');
    $db->beginTransaction();
    $profiles->updateWithExpectedRevision(2, 2, ['updated_by_user_id' => 1]);
    $db->commit();
    bf015RepositoryAssert($profiles->findByOrganizationPropertyId(2)['revision'] === 3, 'Repository rolled back caller commit.');

    bf015RepositoryAssert($measurements->listForProperty(1) === [] && $measurements->findByPropertyAndDefinition(1, 1) === null, 'Empty measurement scope failed.');
    $m1 = $measurements->upsert(['organization_property_id' => 1, 'measurement_definition_id' => 2, 'value_decimal' => '100.5000', 'unit_code' => 'SQM', 'created_by_user_id' => 1, 'updated_by_user_id' => 1]);
    $m2 = $measurements->upsert(['organization_property_id' => 1, 'measurement_definition_id' => 1, 'value_decimal' => '50.0000', 'unit_code' => 'SQM', 'created_by_user_id' => 1, 'updated_by_user_id' => 1]);
    bf015RepositoryAssert(array_column($measurements->listForProperty(1), 'measurement_definition_id') === [1, 2] && $measurements->findByPropertyAndDefinition(1, 2)['id'] === $m1['id'], 'Measurement ordering or scoped find failed.');
    $m1Updated = $measurements->upsert(['organization_property_id' => 1, 'measurement_definition_id' => 2, 'value_decimal' => '101.2500', 'unit_code' => 'SQFT', 'created_by_user_id' => 2, 'updated_by_user_id' => 2]);
    bf015RepositoryAssert($m1Updated['id'] === $m1['id'] && $m1Updated['created_by_user_id'] === 1 && $m1Updated['updated_by_user_id'] === 2 && $m1Updated['unit_code'] === 'SQFT', 'Measurement upsert audit preservation failed.');
    $measurements->upsert(['organization_property_id' => 2, 'measurement_definition_id' => 1, 'value_decimal' => '1.0000', 'unit_code' => 'SQM', 'created_by_user_id' => 1, 'updated_by_user_id' => 1]);
    bf015RepositoryAssert($measurements->delete(1, 1) && ! $measurements->delete(1, 1) && count($measurements->listForProperty(2)) === 1, 'Measurement delete scope failed.');
    bf015RepositoryAssert($measurements->deleteAllForProperty(1) === 1 && count($measurements->listForProperty(2)) === 1, 'Measurement delete-all leaked across property.');
    bf015RepositoryReject(fn () => $measurements->upsert(['organization_property_id' => 1, 'measurement_definition_id' => 999, 'value_decimal' => '1.0000', 'unit_code' => 'SQM', 'created_by_user_id' => 1, 'updated_by_user_id' => 1]), 'Measurement definition FK');
    bf015RepositoryReject(fn () => $measurements->upsert(['organization_property_id' => 1, 'measurement_definition_id' => 1, 'value_decimal' => '0', 'unit_code' => 'SQM', 'created_by_user_id' => 1, 'updated_by_user_id' => 1]), 'Measurement positive value CHECK');
    bf015RepositoryAssert(count($measurements->listForProperty(2)) === 1, 'Repository unusable after measurement failure.');

    foreach ([1 => ['INTEGER', 3, null], 2 => ['DECIMAL', '12.5000', null], 3 => ['BOOLEAN', true, null], 4 => ['TEXT', 'hello', null], 5 => ['ENUM', null, 1], 6 => ['DATE', '2026-01-01', null]] as $definitionId => [$type, $value, $option]) {
        $attributes->upsert(bf015Attribute(1, $definitionId, $type, 1, $value, $option));
    }
    $listed = $attributes->listForProperty(1);
    bf015RepositoryAssert(array_column($listed, 'attribute_definition_id') === [1, 2, 3, 4, 5, 6], 'Attribute ordering failed.');
    $byDefinition = array_column($listed, null, 'attribute_definition_id');
    bf015RepositoryAssert($byDefinition[1]['value_integer'] === 3 && $byDefinition[2]['value_decimal'] === '12.5000' && $byDefinition[3]['value_boolean'] === true && $byDefinition[4]['value_text'] === 'hello' && $byDefinition[5]['attribute_option_id'] === 1 && $byDefinition[6]['value_date'] === '2026-01-01', 'All six attribute representations failed.');
    $replacement = $attributes->upsert(bf015Attribute(1, 1, 'TEXT', 2, 'replaced'));
    bf015RepositoryAssert($replacement['value_text'] === 'replaced' && $replacement['value_integer'] === null && $replacement['value_decimal'] === null && $replacement['value_boolean'] === null && $replacement['value_date'] === null && $replacement['attribute_option_id'] === null && $replacement['created_by_user_id'] === 1, 'Typed replacement did not clear stale columns.');
    $enumReplacement = $attributes->upsert(bf015Attribute(1, 5, 'ENUM', 2, null, 2));
    bf015RepositoryAssert($enumReplacement['attribute_option_id'] === 2 && $enumReplacement['value_text'] === null && $enumReplacement['created_by_user_id'] === 1, 'ENUM replacement failed.');
    $attributes->upsert(bf015Attribute(2, 4, 'TEXT', 1, 'other'));
    bf015RepositoryAssert($attributes->delete(1, 6) && ! $attributes->delete(1, 6) && $attributes->deleteAllForProperty(1) === 5 && count($attributes->listForProperty(2)) === 1, 'Attribute delete scope failed.');
    bf015RepositoryReject(fn () => $pdo->exec("INSERT INTO property_attribute_values(organization_property_id,attribute_definition_id,data_type,value_text,created_by_user_id,updated_by_user_id) VALUES(1,2,'INTEGER','wrong',1,1)"), 'Attribute typed shape CHECK');
    bf015RepositoryReject(fn () => $attributes->upsert(bf015Attribute(1, 5, 'ENUM', 1, null, 999)), 'Attribute option FK');
    bf015RepositoryAssert($attributes->findByPropertyAndDefinition(2, 4)['value_text'] === 'other', 'Repository unusable after attribute constraint failure.');

    echo "BF015 repository MariaDB acceptance: PASS\n";
} finally {
    if ($db !== null && $db->connection()->inTransaction()) {
        $db->connection()->rollBack();
    }
    if ($created) {
        $server->exec("DROP DATABASE `$name`");
    }
}
