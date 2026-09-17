<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\GeographicLocationRepository;
use App\Modules\Property\Repositories\DevelopmentCatalogRepository;
use App\Modules\Property\Services\GeographicLocationService;
use App\Modules\Property\Services\DevelopmentCatalogService;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function e2Assert(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function e2Data(string $code, array $extra = []): array
{
    return $extra + ['code' => $code, 'name_ar' => trim($code), 'name_en' => trim($code)];
}
function e2Snapshot(PDO $pdo): array
{
    $state = [];
    foreach (['geographic_locations', 'developers', 'projects', 'project_phases'] as $table) {
        foreach ($pdo->query("SELECT * FROM `$table` ORDER BY id")->fetchAll(PDO::FETCH_ASSOC) as $row) { $state[$table][$row['id']] = $row; }
        $state[$table] ??= [];
    }
    return $state;
}
function e2Rejected(PDO $pdo, callable $operation, string $code): void
{
    $before = e2Snapshot($pdo);
    try { $operation(); throw new RuntimeException('Expected ' . $code); }
    catch (ValidationException $exception) { e2Assert(isset($exception->errors()[$code]), 'Expected ' . $code . ', got ' . json_encode($exception->errors())); }
    e2Assert(e2Snapshot($pdo) === $before, $code . ' changed persisted state');
    e2Assert(!$pdo->inTransaction(), $code . ' left a transaction open');
}
/** A successful transition may change only the requested row's lifecycle/audit fields. */
function e2Transition(PDO $pdo, string $table, int $id, string $status, callable $operation): void
{
    $before = e2Snapshot($pdo); $operation(); $after = e2Snapshot($pdo);
    e2Assert($after[$table][$id]['status'] === $status, 'Requested status not persisted');
    foreach (['status', 'updated_at', 'updated_by_user_id'] as $field) { $before[$table][$id][$field] = $after[$table][$id][$field]; }
    e2Assert($before === $after, 'Lifecycle cascade or unrelated mutation: ' . $table);
}
function e2Deleted(PDO $pdo, string $table, int $id, callable $operation): void
{
    $before = e2Snapshot($pdo);
    e2Assert($operation() === true, 'Safe deletion failed');
    unset($before[$table][$id]);
    e2Assert(e2Snapshot($pdo) === $before, 'Deletion cascaded or target remains');
}

$root = dirname(__DIR__, 2); Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php'; $connection = $config['connections']['mysql'];
$name = 'directors_resale_platform_e2_test_' . getmypid();
e2Assert(preg_match('/^directors_resale_platform_e2_test_[0-9]+$/D', $name) === 1 && $name !== $connection['database'], 'Unsafe database');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
e2Assert(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB required');
$created = false; $database = null;
try {
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created = true;
    $config['connections']['mysql']['database'] = $name; $database = new DatabaseManager($config); $pdo = $database->connection();
    $files = glob($root . '/database/migrations/*.sql') ?: []; sort($files);
    foreach ($files as $file) { $pdo->exec(file_get_contents($file)); }
    $container = new Container(); (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $database);
    $geo = $container->make(GeographicLocationService::class);
    $dev = $container->make(DevelopmentCatalogService::class);
    $locations = $container->make(GeographicLocationRepository::class);
    $development = $container->make(DevelopmentCatalogRepository::class);

    // A: full hierarchy, skipped levels, and atomic structural rejection.
    $chain = []; $parent = null;
    foreach (['COUNTRY' => ' egypt ', 'GOVERNORATE' => 'Cairo', 'CITY' => 'New Cairo', 'AREA' => 'Fifth Settlement', 'DISTRICT' => 'District One'] as $type => $label) {
        $row = $geo->createLocation(e2Data($label, ['location_type' => $type, 'parent_id' => $parent]));
        e2Assert($locations->findGeographicLocationById($row['id']) === $row && $row['parent_id'] === $parent && $row['location_type'] === $type, 'Persisted hierarchy level');
        $chain[$type] = $row; $parent = $row['id'];
    }
    $country = $chain['COUNTRY']; $governorate = $chain['GOVERNORATE']; $city = $chain['CITY']; $area = $chain['AREA']; $district = $chain['DISTRICT'];
    e2Assert($country['parent_id'] === null && $country['code'] === 'EGYPT', 'Country root and normalization');
    $skipped = $geo->createLocation(e2Data('Skipped District', ['location_type' => 'DISTRICT', 'parent_id' => $country['id']]));
    $ancestry = $geo->loadAncestry($district['id']);
    e2Assert($ancestry['stop_reason'] === 'root' && array_column($ancestry['locations'], 'id') === array_reverse(array_column(array_values($chain), 'id')), 'Persisted iterative ancestry order');
    e2Rejected($pdo, fn() => $geo->createLocation(e2Data('Bad root', ['location_type' => 'COUNTRY', 'parent_id' => $country['id']])), 'GEOGRAPHY_INVALID_PARENT_LEVEL');
    foreach (['GOVERNORATE', 'CITY', 'AREA', 'DISTRICT'] as $type) {
        e2Rejected($pdo, fn() => $geo->createLocation(e2Data('No parent', ['location_type' => $type])), 'GEOGRAPHY_INVALID_PARENT_LEVEL');
    }
    e2Rejected($pdo, fn() => $geo->createLocation(e2Data('Equal rank', ['location_type' => 'CITY', 'parent_id' => $city['id']])), 'GEOGRAPHY_INVALID_PARENT_LEVEL');
    e2Rejected($pdo, fn() => $geo->createLocation(e2Data('Lower parent', ['location_type' => 'CITY', 'parent_id' => $area['id']])), 'GEOGRAPHY_INVALID_PARENT_LEVEL');
    e2Rejected($pdo, fn() => $geo->updateLocation($city['id'], ['parent_id' => $city['id'], 'name_en' => 'Rejected self']), 'GEOGRAPHY_SELF_PARENT');
    e2Rejected($pdo, fn() => $geo->updateLocation($governorate['id'], ['parent_id' => $district['id'], 'name_en' => 'Rejected cycle']), 'GEOGRAPHY_CYCLE_DETECTED');
    $moved = $geo->updateLocation($skipped['id'], ['parent_id' => $governorate['id']]);
    e2Assert($moved['parent_id'] === $governorate['id'] && array_column($geo->loadAncestry($skipped['id'])['locations'], 'id') === [$skipped['id'], $governorate['id'], $country['id']], 'Reassignment persisted');
    echo "Journey A: PASS\n";

    // B: full lifecycle sequence, checking every table for unintended cascades.
    e2Rejected($pdo, fn() => $geo->deactivateLocation($country['id']), 'LOCATION_HAS_ACTIVE_DESCENDANTS');
    e2Transition($pdo, 'geographic_locations', $skipped['id'], 'inactive', fn() => $geo->deactivateLocation($skipped['id']));
    foreach (array_reverse($chain) as $row) { e2Transition($pdo, 'geographic_locations', $row['id'], 'inactive', fn() => $geo->deactivateLocation($row['id'])); }
    e2Rejected($pdo, fn() => $geo->reactivateLocation($district['id']), 'PARENT_CATALOG_INACTIVE');
    foreach ($chain as $row) { e2Transition($pdo, 'geographic_locations', $row['id'], 'active', fn() => $geo->reactivateLocation($row['id'])); }
    e2Transition($pdo, 'geographic_locations', $skipped['id'], 'active', fn() => $geo->reactivateLocation($skipped['id']));
    $wide = $geo->createLocation(e2Data('Wide root', ['location_type' => 'COUNTRY']));
    for ($index = 0; $index < 51; $index++) {
        $last = $geo->createLocation(e2Data('Wide-' . $index, ['location_type' => 'CITY', 'parent_id' => $wide['id']]));
        if ($index < 50) { e2Transition($pdo, 'geographic_locations', $last['id'], 'inactive', fn() => $geo->deactivateLocation($last['id'])); }
    }
    e2Assert(count($locations->listChildren($wide['id'])) === 50 && $locations->listChildren($wide['id'], ['offset' => 50])[0]['id'] === $last['id'], 'Active blocker lies beyond first page');
    e2Rejected($pdo, fn() => $geo->deactivateLocation($wide['id']), 'LOCATION_HAS_ACTIVE_DESCENDANTS');
    e2Transition($pdo, 'geographic_locations', $last['id'], 'inactive', fn() => $geo->deactivateLocation($last['id']));
    e2Transition($pdo, 'geographic_locations', $wide['id'], 'inactive', fn() => $geo->deactivateLocation($wide['id']));
    echo "Journey B: PASS\n";

    // C: optional Developer relationships and coupled lifecycle rules.
    $developer = $dev->createDeveloper(e2Data('Primary Developer')); $other = $dev->createDeveloper(e2Data('Other Developer'));
    $project = $dev->createProject(e2Data('Main Project', ['developer_id' => $developer['id'], 'geographic_location_id' => $district['id']]));
    e2Assert($development->findProjectById($project['id']) === $project, 'Persisted integrated Project');
    $plain = $dev->createProject(e2Data('Independent Project'));
    e2Assert($plain['developer_id'] === null && $plain['geographic_location_id'] === null, 'Optional relationships');
    foreach ([$developer['id'], $other['id'], null] as $developerId) {
        $dev->updateProject($plain['id'], ['developer_id' => $developerId]);
        e2Assert($development->findProjectById($plain['id'])['developer_id'] === $developerId, 'Developer assignment/reassignment/removal');
    }
    e2Rejected($pdo, fn() => $dev->deactivateDeveloper($developer['id']), 'DEVELOPER_HAS_ACTIVE_PROJECTS');
    e2Transition($pdo, 'projects', $project['id'], 'inactive', fn() => $dev->deactivateProject($project['id']));
    e2Transition($pdo, 'developers', $developer['id'], 'inactive', fn() => $dev->deactivateDeveloper($developer['id']));
    e2Rejected($pdo, fn() => $dev->updateProject($plain['id'], ['developer_id' => $developer['id'], 'name_en' => 'Rejected assignment']), 'PARENT_CATALOG_INACTIVE');
    $dev->updateProject($plain['id'], ['developer_id' => $other['id']]);
    e2Rejected($pdo, fn() => $dev->updateProject($plain['id'], ['developer_id' => $developer['id']]), 'PARENT_CATALOG_INACTIVE');
    $dev->updateProject($plain['id'], ['developer_id' => null]);
    e2Rejected($pdo, fn() => $dev->reactivateProject($project['id']), 'PARENT_CATALOG_INACTIVE');
    e2Transition($pdo, 'developers', $developer['id'], 'active', fn() => $dev->reactivateDeveloper($developer['id']));
    e2Transition($pdo, 'projects', $project['id'], 'active', fn() => $dev->reactivateProject($project['id']));
    echo "Journey C: PASS\n";

    // D: Geography is validated on assignment, not a continuing lifecycle parent.
    e2Transition($pdo, 'geographic_locations', $district['id'], 'inactive', fn() => $geo->deactivateLocation($district['id']));
    e2Assert($dev->findProject($project['id'])['status'] === 'active', 'Geography must not deactivate Project');
    e2Transition($pdo, 'projects', $project['id'], 'inactive', fn() => $dev->deactivateProject($project['id']));
    e2Transition($pdo, 'projects', $project['id'], 'active', fn() => $dev->reactivateProject($project['id']));
    e2Assert($dev->findProject($project['id'])['geographic_location_id'] === $district['id'], 'Reactivation preserves inactive Geography reference');
    e2Rejected($pdo, fn() => $dev->updateProject($plain['id'], ['geographic_location_id' => $district['id'], 'name_en' => 'Rejected geography']), 'PARENT_CATALOG_INACTIVE');
    foreach ([$skipped['id'], null] as $locationId) {
        $dev->updateProject($project['id'], ['geographic_location_id' => $locationId]);
        e2Assert($development->findProjectById($project['id'])['geographic_location_id'] === $locationId, 'Geography reassignment/removal');
    }
    echo "Journey D: PASS\n";

    // E: required immutable Phase parent and explicit child/parent transitions.
    e2Rejected($pdo, fn() => $dev->createProjectPhase(0, e2Data('No project')), 'PARENT_CATALOG_INACTIVE');
    e2Rejected($pdo, fn() => $dev->createProjectPhase(999999, e2Data('Missing project')), 'PARENT_CATALOG_INACTIVE');
    $phase = $dev->createProjectPhase($project['id'], e2Data('Phase One'));
    e2Assert($development->findProjectPhaseById($phase['id']) === $phase && $phase['project_id'] === $project['id'], 'Persisted Phase parent');
    e2Rejected($pdo, fn() => $dev->updateProjectPhase($project['id'], $phase['id'], ['project_id' => $plain['id'], 'name_en' => 'Rejected move']), 'CATALOG_IDENTITY_IMMUTABLE');
    e2Rejected($pdo, fn() => $dev->deactivateProject($project['id']), 'PROJECT_HAS_ACTIVE_PHASES');
    e2Transition($pdo, 'project_phases', $phase['id'], 'inactive', fn() => $dev->deactivateProjectPhase($project['id'], $phase['id']));
    e2Transition($pdo, 'projects', $project['id'], 'inactive', fn() => $dev->deactivateProject($project['id']));
    e2Rejected($pdo, fn() => $dev->createProjectPhase($project['id'], e2Data('Inactive parent')), 'PARENT_CATALOG_INACTIVE');
    e2Rejected($pdo, fn() => $dev->reactivateProjectPhase($project['id'], $phase['id']), 'PARENT_CATALOG_INACTIVE');
    e2Transition($pdo, 'projects', $project['id'], 'active', fn() => $dev->reactivateProject($project['id']));
    e2Transition($pdo, 'project_phases', $phase['id'], 'active', fn() => $dev->reactivateProjectPhase($project['id'], $phase['id']));
    echo "Journey E: PASS\n";

    // F: inactive references still protect rows; every delete is non-cascading.
    $dev->updateProject($project['id'], ['geographic_location_id' => $skipped['id']]);
    e2Transition($pdo, 'project_phases', $phase['id'], 'inactive', fn() => $dev->deactivateProjectPhase($project['id'], $phase['id']));
    e2Transition($pdo, 'projects', $project['id'], 'inactive', fn() => $dev->deactivateProject($project['id']));
    e2Transition($pdo, 'developers', $developer['id'], 'inactive', fn() => $dev->deactivateDeveloper($developer['id']));
    e2Transition($pdo, 'geographic_locations', $skipped['id'], 'inactive', fn() => $geo->deactivateLocation($skipped['id']));
    foreach ([fn() => $dev->deleteDeveloper($developer['id']), fn() => $dev->deleteProject($project['id']), fn() => $geo->deleteLocation($skipped['id']), fn() => $geo->deleteLocation($wide['id'])] as $operation) {
        e2Rejected($pdo, $operation, 'CATALOG_ITEM_REFERENCED');
    }
    $seedGeo = $geo->createLocation(e2Data('Seed country', ['location_type' => 'COUNTRY', 'provenance' => 'SYSTEM_SEED']));
    $seedDev = $dev->createDeveloper(e2Data('Seed developer', ['provenance' => 'SYSTEM_SEED']));
    $seedProject = $dev->createProject(e2Data('Seed project', ['provenance' => 'SYSTEM_SEED']));
    $seedPhase = $dev->createProjectPhase($seedProject['id'], e2Data('Seed phase', ['provenance' => 'SYSTEM_SEED']));
    foreach ([fn() => $geo->deleteLocation($seedGeo['id']), fn() => $dev->deleteDeveloper($seedDev['id']), fn() => $dev->deleteProject($seedProject['id']), fn() => $dev->deleteProjectPhase($seedProject['id'], $seedPhase['id'])] as $operation) {
        e2Rejected($pdo, $operation, 'SYSTEM_SEED_DELETE_FORBIDDEN');
    }
    $freeGeo = $geo->createLocation(e2Data('Free country', ['location_type' => 'COUNTRY']));
    $freeDev = $dev->createDeveloper(e2Data('Free developer'));
    $freeProject = $dev->createProject(e2Data('Free project'));
    $freePhase = $dev->createProjectPhase($freeProject['id'], e2Data('Free phase'));
    e2Deleted($pdo, 'project_phases', $freePhase['id'], fn() => $dev->deleteProjectPhase($freeProject['id'], $freePhase['id']));
    e2Deleted($pdo, 'projects', $freeProject['id'], fn() => $dev->deleteProject($freeProject['id']));
    e2Deleted($pdo, 'developers', $freeDev['id'], fn() => $dev->deleteDeveloper($freeDev['id']));
    e2Deleted($pdo, 'geographic_locations', $freeGeo['id'], fn() => $geo->deleteLocation($freeGeo['id']));
    echo "Journey F: PASS\n";

    // G: every rejection above compares complete persisted rows across all four
    // catalogs, including relationship, presentation and audit fields.
    foreach (['id', 'ulid', 'code', 'provenance', 'status'] as $field) {
        e2Rejected($pdo, fn() => $geo->updateLocation($country['id'], [$field => $country[$field]]), 'CATALOG_IDENTITY_IMMUTABLE');
        e2Rejected($pdo, fn() => $dev->updateDeveloper($developer['id'], [$field => $developer[$field]]), 'CATALOG_IDENTITY_IMMUTABLE');
        e2Rejected($pdo, fn() => $dev->updateProject($project['id'], [$field => $project[$field]]), 'CATALOG_IDENTITY_IMMUTABLE');
        e2Rejected($pdo, fn() => $dev->updateProjectPhase($project['id'], $phase['id'], [$field => $phase[$field]]), 'CATALOG_IDENTITY_IMMUTABLE');
    }
    e2Transition($pdo, 'developers', $developer['id'], 'active', fn() => $dev->reactivateDeveloper($developer['id']));
    e2Transition($pdo, 'projects', $project['id'], 'active', fn() => $dev->reactivateProject($project['id']));
    e2Transition($pdo, 'project_phases', $phase['id'], 'active', fn() => $dev->reactivateProjectPhase($project['id'], $phase['id']));
    $finalProject = $development->findProjectById($project['id']);
    e2Assert($finalProject['status'] === 'active' && $finalProject['developer_id'] === $developer['id'] && $finalProject['geographic_location_id'] === $skipped['id'], 'Final integrated Project relationships');
    e2Assert($geo->findLocation($skipped['id'])['status'] === 'inactive' && $dev->findProjectPhase($phase['id'])['status'] === 'active', 'Final loose coupling and Phase state');
    e2Assert(array_column($geo->loadAncestry($district['id'])['locations'], 'id') === array_reverse(array_column(array_values($chain), 'id')), 'Rejected operations changed final hierarchy');
    e2Assert(count($locations->listChildren($wide['id'], ['limit' => 200, 'status' => 'inactive'])) === 51 && $geo->findLocation($wide['id'])['status'] === 'inactive', 'Final paginated subtree state');
    e2Assert(!$pdo->inTransaction(), 'Open transaction residue');
    echo "Journey G: PASS; rejected operations preserved all four catalogs\n";
    echo "E2 Geography + Development integrated MariaDB acceptance: PASS\n";
} finally {
    if ($database !== null && $database->connection()->inTransaction()) { $database->rollback(); }
    if ($created) { $server->exec("DROP DATABASE `$name`"); }
    $remaining = $server->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?'); $remaining->execute([$name]);
    e2Assert((int) $remaining->fetchColumn() === 0, 'Temporary database residue');
}
