<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Modules\Authorization\Services\AuthorizationService;
use App\Modules\Permission\Services\PositionPermissionService;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function bf014PermissionAssert(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$connection = $config['connections']['mysql'];
$developmentDatabase = (string) $connection['database'];
$databaseName = 'directors_resale_platform_bf014_permission_test_' . getmypid();
$safeName = static fn (string $name): bool => preg_match('/^directors_resale_platform_bf014_permission_test_[0-9]+$/D', $name) === 1;
bf014PermissionAssert($safeName($databaseName) && $databaseName !== $developmentDatabase, 'Unsafe test database name.');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), (string) $connection['username'], (string) $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
$database = null;
$cleanup = static function () use ($server, $databaseName, $developmentDatabase, $safeName, &$created, &$database): void {
    if (! $created) { return; }
    if ($database !== null && $database->connection()->inTransaction()) { $database->rollback(); }
    bf014PermissionAssert($safeName($databaseName) && $databaseName !== $developmentDatabase, 'Unsafe cleanup refused.');
    $server->exec("DROP DATABASE `{$databaseName}`");
    $created = false;
};
register_shutdown_function($cleanup);

try {
    bf014PermissionAssert(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB is required.');
    $server->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $config['connections']['mysql']['database'] = $databaseName;
    $database = new DatabaseManager($config);
    $pdo = $database->connection();
    $migrations = glob($root . '/database/migrations/*.sql') ?: [];
    sort($migrations, SORT_STRING);
    foreach ($migrations as $migration) { $pdo->exec((string) file_get_contents($migration)); }

    $permissions = $pdo->query("SELECT code, status FROM permissions ORDER BY code")->fetchAll(PDO::FETCH_ASSOC);
    bf014PermissionAssert(count($permissions) === 32, 'Migration 032 must increase the active Permission catalog from 30 to 32.');
    $catalogCodes = array_values(array_column(array_filter($permissions, static fn (array $row): bool => str_starts_with($row['code'], 'property_catalogs.')), 'code'));
    sort($catalogCodes, SORT_STRING);
    bf014PermissionAssert($catalogCodes === ['property_catalogs.manage', 'property_catalogs.view'], 'Migration 032 must add exactly the two locked catalog capabilities.');
    foreach ($permissions as $permission) {
        if (str_starts_with($permission['code'], 'property_catalogs.')) {
            bf014PermissionAssert($permission['status'] === 'active', 'Catalog capabilities must be active.');
        }
    }
    try {
        $pdo->exec("INSERT INTO permissions (code, name, status) VALUES ('property_catalogs.view', 'Duplicate', 'active')");
        throw new RuntimeException('Duplicate Permission code constraint was not enforced.');
    } catch (PDOException) {}

    $pdo->exec("INSERT INTO organizations (name, code, organization_type, status) VALUES ('System', 'SYSTEM', 'system', 'active')");
    $organizationId = (int) $pdo->lastInsertId();
    $pdo->exec("INSERT INTO positions (organization_id, name, code, status) VALUES ({$organizationId}, 'Catalog Position', 'CATALOG', 'active'), ({$organizationId}, 'No Catalog Position', 'NO_CATALOG', 'active')");
    $positionId = (int) $pdo->query("SELECT id FROM positions WHERE code = 'CATALOG'")->fetchColumn();
    $emptyPositionId = (int) $pdo->query("SELECT id FROM positions WHERE code = 'NO_CATALOG'")->fetchColumn();

    $container = new Container();
    (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $database);
    $assignments = $container->make(PositionPermissionService::class);
    $authorization = $container->make(AuthorizationService::class);
    $assigned = $assignments->replace($positionId, ['property_catalogs.view', 'property_catalogs.manage']);
    bf014PermissionAssert(array_column($assigned, 'code') === ['property_catalogs.view', 'property_catalogs.manage'] || array_column($assigned, 'code') === ['property_catalogs.manage', 'property_catalogs.view'], 'Existing Position permission assignment must resolve both catalog capabilities.');
    $actor = ['organization_id' => $organizationId, 'position_id' => $positionId, 'status' => 'active'];
    $missing = ['organization_id' => $organizationId, 'position_id' => $emptyPositionId, 'status' => 'active'];
    bf014PermissionAssert($authorization->authorize($actor, 'property_catalogs.view') === null, 'Assigned catalog view capability must authorize through existing machinery.');
    bf014PermissionAssert($authorization->authorize($actor, 'property_catalogs.manage') === null, 'Assigned catalog manage capability must authorize through existing machinery.');
    bf014PermissionAssert($authorization->authorize($missing, 'property_catalogs.view') !== null, 'Missing catalog capability must remain denied.');
    $pdo->exec("UPDATE permissions SET status = 'inactive' WHERE code = 'property_catalogs.view'");
    bf014PermissionAssert($authorization->authorize($actor, 'property_catalogs.view') !== null, 'Inactive catalog capability must not authorize.');
    bf014PermissionAssert($authorization->authorize($actor, 'property_catalogs.manage') === null, 'Inactive view capability must not affect active manage capability.');

    echo "BF014.4A Permission foundation MariaDB acceptance: PASS\n";
} finally {
    $cleanup();
}
