<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Http\HeaderBag;
use App\Http\InputBag;
use App\Http\Request;
use App\Providers\AppServiceProvider;
use App\Responses\Response;
use App\Routing\Router;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$assertions = 0;
function hierarchyAssert(bool $condition, string $message): void
{
    global $assertions;
    ++$assertions;
    if (! $condition) { throw new RuntimeException($message); }
}
function hierarchyRequest(Router $router, string $method, string $uri, ?string $token, array $data = []): array
{
    parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
    $path = (string) parse_url($uri, PHP_URL_PATH);
    $request = new Request(new InputBag($query), new InputBag([]), new InputBag($data), new HeaderBag(
        $token === null ? [] : ['Authorization' => 'Bearer ' . $token]
    ), new InputBag([]), new InputBag([]), [], $method, $path);
    $response = $router->dispatch($method, $path, $request);
    $result = [];
    foreach (['statusCode' => 'status', 'payload' => 'payload'] as $property => $key) {
        $reflection = new ReflectionProperty(Response::class, $property);
        $reflection->setAccessible(true);
        $result[$key] = $reflection->getValue($response);
    }
    return $result;
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$real = $config['connections']['mysql'];
$databaseName = 'directors_resale_platform_franchise_security_' . getmypid();
hierarchyAssert($real['host'] === '127.0.0.1' && (int) $real['port'] === 3306 && $real['database'] === 'directors_resale_platform', 'Unexpected real configuration.');
hierarchyAssert((bool) preg_match('/^directors_resale_platform_franchise_security_[0-9]+$/D', $databaseName) && $databaseName !== $real['database'], 'Unsafe disposable target.');
$server = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', $real['username'], $real['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$exists = $server->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');
$exists->execute([$databaseName]);
hierarchyAssert((int) $exists->fetchColumn() === 0, 'Refusing to reuse an existing database.');
$created = false;
try {
    $server->exec("CREATE DATABASE `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $config['connections']['mysql']['database'] = $databaseName;
    $database = new DatabaseManager($config);
    $pdo = $database->connection();
    hierarchyAssert($pdo->query('SELECT DATABASE()')->fetchColumn() === $databaseName, 'Setup must use only the disposable database.');
    $migrations = glob($root . '/database/migrations/*.sql') ?: [];
    sort($migrations, SORT_STRING);
    foreach ($migrations as $migration) { $pdo->exec((string) file_get_contents($migration)); }

    $pdo->exec("INSERT INTO organizations (id,parent_organization_id,name,code,organization_type,status) VALUES
        (1,NULL,'System','SYS','system','active'),(2,1,'Own Franchise','OWN','franchise','active'),
        (3,2,'Own Child','CHILD','partner_agency','active'),(4,1,'Peer Franchise','PEER','franchise','active'),
        (5,4,'Peer Child','PEERCHILD','partner_agency','active')");
    $pdo->exec("INSERT INTO positions (id,organization_id,name,code,status) VALUES
        (1,1,'System Admin','SYSADMIN','active'),(2,2,'Franchise Admin','ADMIN','active'),
        (3,3,'Child Admin','ADMIN','active'),(4,4,'Peer Admin','ADMIN','active')");
    $users = $pdo->prepare('INSERT INTO users (id,organization_id,position_id,full_name,email,status,password_hash) VALUES (?,?,?,?,?,?,?)');
    $tokens = $pdo->prepare('INSERT INTO auth_tokens (user_id,token_hash,expires_at) VALUES (?,?,?)');
    foreach ([1 => 'system', 2 => 'franchise', 3 => 'child', 4 => 'peer'] as $id => $token) {
        $users->execute([$id, $id, $id, 'Fixture Admin', $token . '@example.test', 'active', password_hash('fixture-only', PASSWORD_DEFAULT)]);
        $tokens->execute([$id, hash('sha256', $token), '2999-01-01']);
    }
    $codes = [
        'organizations.view', 'organizations.update', 'users.view', 'users.create', 'users.update', 'users.archive',
        'positions.view', 'positions.create', 'positions.update', 'positions.archive', 'permissions.view', 'permissions.assign',
        'partner_agencies.view', 'partner_agencies.create', 'partner_agencies.update', 'partner_agencies.archive',
        'properties.view', 'properties.manage', 'owners.view', 'owners.manage', 'ownerships.view', 'ownerships.manage', 'property_catalogs.view',
    ];
    $assign = $pdo->prepare('INSERT INTO position_permissions (position_id,permission_id) SELECT ?,id FROM permissions WHERE code=?');
    $pdo->exec('INSERT INTO position_permissions (position_id,permission_id) SELECT 1,id FROM permissions');
    foreach ([2, 3, 4] as $position) { foreach ($codes as $code) { $assign->execute([$position, $code]); } }
    $pdo->exec("INSERT INTO organization_properties (id,ulid,organization_id,property_label,status,created_by_user_id,updated_by_user_id) VALUES
        (1,'01ARZ3NDEKTSV4RRFFQ69G5FAV',2,'Own Property','active',2,2),
        (2,'01ARZ3NDEKTSV4RRFFQ69G5FAW',3,'Child Property','active',3,3)");

    $container = new Container();
    (new AppServiceProvider($container, ['app' => ['auth' => ['token_ttl' => 3600]], 'database' => $config]))->register();
    $container->instance(DatabaseManager::class, $database);
    $container->instance(DatabaseConnectionInterface::class, $database);
    $router = $container->make(Router::class);
    (require $root . '/routes/api.php')($router, $container, ['app' => []]);

    foreach ([2, 3] as $id) {
        $before = $pdo->query('SELECT * FROM organizations WHERE id=' . $id)->fetch(PDO::FETCH_ASSOC);
        foreach (['system', ' SYSTEM ', 'System'] as $type) {
            $response = hierarchyRequest($router, 'PUT', '/organizations/' . $id, 'franchise', ['organization_type' => $type, 'name' => 'Must not persist']);
            hierarchyAssert($response['status'] === 403 && $pdo->query('SELECT * FROM organizations WHERE id=' . $id)->fetch(PDO::FETCH_ASSOC) === $before, 'Type escalation must be denied atomically for own/child Organizations.');
        }
    }
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/organizations/3', 'franchise', ['organization_type' => 'franchise'])['status'] === 403, 'Child type must not become a peer Franchise through generic update.');
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/organizations/2', 'franchise', ['name' => 'Own Edited', 'organization_type' => 'franchise'])['status'] === 200, 'Valid own Organization update must succeed.');
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/organizations/3', 'franchise', ['name' => 'Child Edited'])['status'] === 200, 'Valid child Organization update must succeed.');
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/organizations/2/basic-profile', 'franchise', ['address_text' => 'Own address'])['status'] === 200, 'Own Basic Profile update must succeed.');
    hierarchyAssert(hierarchyRequest($router, 'GET', '/organizations/2/basic-profile', 'franchise')['payload']['data']['profile']['address_text'] === 'Own address', 'Own Basic Profile must round-trip.');

    foreach ([1, 4] as $id) {
        hierarchyAssert(hierarchyRequest($router, 'GET', '/organizations/' . $id, 'franchise')['status'] === 403 && hierarchyRequest($router, 'PUT', '/organizations/' . $id, 'franchise', ['name' => 'Denied'])['status'] === 403, 'System/peer access and updates must remain denied.');
    }
    hierarchyAssert(hierarchyRequest($router, 'POST', '/franchises', 'franchise', ['parent_organization_id' => 1])['status'] === 403, 'Peer Franchise creation must remain denied.');
    hierarchyAssert(hierarchyRequest($router, 'POST', '/network/franchises/onboard', 'franchise', ['franchise' => ['parent_organization_id' => 1]])['status'] === 403, 'Franchise onboarding must remain denied.');
    hierarchyAssert(hierarchyRequest($router, 'POST', '/organizations', 'franchise', ['organization_type' => 'franchise'])['status'] === 403, 'Generic Organization creation is not part of the proposed role.');
    $assign->execute([2, 'organizations.create']);
    hierarchyAssert(hierarchyRequest($router, 'POST', '/organizations', 'franchise', ['organization_id' => 2, 'name' => 'Denied', 'code' => 'DENIEDSYSTEM', 'organization_type' => 'system', 'status' => 'active'])['status'] === 403 && (int) $pdo->query("SELECT COUNT(*) FROM organizations WHERE code='DENIEDSYSTEM'")->fetchColumn() === 0, 'Even organizations.create must not let a non-System actor create a System Organization.');
    $pdo->exec("DELETE pp FROM position_permissions pp JOIN permissions p ON p.id=pp.permission_id WHERE pp.position_id=2 AND p.code='organizations.create'");
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/organizations/5', 'system', ['organization_type' => 'system'])['status'] === 200, 'System-authorized type change must remain available.');
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/organizations/5', 'system', ['organization_type' => 'partner_agency'])['status'] === 200, 'System-authorized type restoration must remain available.');
    hierarchyAssert(hierarchyRequest($router, 'POST', '/organizations', 'system', ['organization_id' => 1, 'name' => 'System Created', 'code' => 'NEWSYS', 'organization_type' => 'system', 'status' => 'active'])['status'] === 201, 'System-authorized System creation must remain available.');

    hierarchyAssert(hierarchyRequest($router, 'PUT', '/partner-agencies/3', 'franchise', ['name' => 'Valid child edit', 'parent_organization_id' => '02'])['status'] === 200, 'Valid child update with same authorized parent must succeed.');
    $childBefore = $pdo->query('SELECT * FROM organizations WHERE id=3')->fetch(PDO::FETCH_ASSOC);
    foreach ([4, '04', 1, 999999] as $parent) {
        hierarchyAssert(hierarchyRequest($router, 'PUT', '/partner-agencies/3', 'franchise', ['parent_organization_id' => $parent, 'name' => 'Must not persist'])['status'] === 403 && $pdo->query('SELECT * FROM organizations WHERE id=3')->fetch(PDO::FETCH_ASSOC) === $childBefore, 'Outside/peer/System/nonexistent parent must be rejected without partial mutation.');
    }
    foreach ([null, [], 4.0, '4e0', ' 4 ', 0] as $parent) {
        hierarchyAssert(hierarchyRequest($router, 'PUT', '/partner-agencies/3', 'franchise', ['parent_organization_id' => $parent])['status'] === 422, 'Malformed parent must retain validation rejection.');
    }
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/partner-agencies/3', 'franchise', ['parent_organization_id' => 3])['status'] === 422, 'A child Partner cannot be its own parent.');
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/partner-agencies/5', 'franchise', ['parent_organization_id' => 2])['status'] === 403, 'Own new parent must not authorize taking another Franchise child.');
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/partner-agencies/3', 'system', ['parent_organization_id' => 4])['status'] === 200, 'System-authorized valid reparenting must remain available.');
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/partner-agencies/3', 'system', ['parent_organization_id' => 2])['status'] === 200, 'System-authorized parent restoration must succeed.');
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/partner-agencies/3', 'system', ['parent_organization_id' => 1])['status'] === 422, 'Even System must obey Franchise-parent validation.');
    $newChild = hierarchyRequest($router, 'POST', '/partner-agencies', 'franchise', ['name' => 'Bare Child', 'code' => 'BARE', 'parent_organization_id' => 2, 'status' => 'active']);
    hierarchyAssert($newChild['status'] === 201 && (int) $newChild['payload']['data']['parent_organization_id'] === 2, 'Existing bare child creation must remain available.');
    hierarchyAssert(hierarchyRequest($router, 'POST', '/partner-agencies', 'franchise', ['parent_organization_id' => 4])['status'] === 403, 'Child creation under a peer must remain denied.');

    foreach (['/organizations/3/basic-profile', '/organization-properties/2/profile', '/organization-properties/2/primary-image', '/owners?organization_id=3'] as $uri) {
        hierarchyAssert(hierarchyRequest($router, 'GET', $uri, 'franchise')['status'] === 403, 'Parent administrative hierarchy must not expose child private data.');
    }
    hierarchyAssert(hierarchyRequest($router, 'GET', '/organization-properties/2/profile', 'child')['status'] === 200, 'Child own private reads remain available.');
    hierarchyAssert(hierarchyRequest($router, 'GET', '/users/3', 'franchise')['status'] === 200 && hierarchyRequest($router, 'GET', '/positions/3', 'franchise')['status'] === 200, 'Own child User/Position administration must remain available.');
    foreach ([1, 4] as $position) {
        hierarchyAssert(hierarchyRequest($router, 'PUT', '/positions/' . $position . '/permissions', 'franchise', ['permissions' => ['properties.view']])['status'] === 403, 'Outside hierarchy permission delegation must remain denied.');
    }
    $assignmentsBefore = $pdo->query('SELECT * FROM position_permissions WHERE position_id=3 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/positions/3/permissions', 'franchise', ['permissions' => ['properties.view', 'property_catalogs.manage']])['status'] === 422 && $pdo->query('SELECT * FROM position_permissions WHERE position_id=3 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) === $assignmentsBefore, 'System-only capability outside the actor subset must not be delegated.');
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/positions/3/permissions', 'franchise', ['permissions' => ['properties.view', 'users.view']])['status'] === 200, 'Actor capability subset may be delegated to an own child Position.');
    $assign->execute([2, 'property_catalogs.manage']);
    hierarchyAssert(hierarchyRequest($router, 'POST', '/property-categories', 'franchise', [])['status'] === 403, 'SYSTEM_ONLY scope remains authoritative even with the capability.');
    $pdo->exec("DELETE pp FROM position_permissions pp JOIN permissions p ON p.id=pp.permission_id WHERE pp.position_id=2 AND p.code='property_catalogs.manage'");
    $context = hierarchyRequest($router, 'GET', '/auth/context', 'franchise');
    hierarchyAssert($context['status'] === 200 && count($context['payload']['data']['permissions']) === 23, 'Proposed role must expose exactly 23 capabilities.');
    hierarchyAssert(hierarchyRequest($router, 'PUT', '/organizations/2', null, ['organization_type' => 'system'])['status'] === 401 && hierarchyRequest($router, 'PUT', '/partner-agencies/3', null, ['parent_organization_id' => 4])['status'] === 401, 'Anonymous mutations remain denied.');
    echo "Franchise Admin hierarchy security MariaDB acceptance: PASS ($assertions assertions)\n";
} finally {
    if ($created) {
        hierarchyAssert((bool) preg_match('/^directors_resale_platform_franchise_security_[0-9]+$/D', $databaseName) && $databaseName !== $real['database'], 'Unsafe cleanup target.');
        $server->exec("DROP DATABASE `$databaseName`");
        $exists->execute([$databaseName]);
        hierarchyAssert((int) $exists->fetchColumn() === 0, 'Disposable database cleanup failed.');
        echo "Disposable database cleanup: PASS\n";
    }
}
