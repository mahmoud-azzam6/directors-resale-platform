<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\DatabaseManager;
use App\Core\Database\DatabaseConnectionInterface;
use App\Http\HeaderBag;
use App\Http\InputBag;
use App\Http\Request;
use App\Providers\AppServiceProvider;
use App\Routing\Router;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function oa(bool $condition, string $message): void { if (! $condition) { throw new RuntimeException($message); } }
function oaRequest(Router $router, string $method, string $uri, ?string $token = null, array $data = []): array {
    $headers = $token === null ? [] : ['Authorization' => 'Bearer ' . $token];
    $request = new Request(new InputBag([]), new InputBag([]), new InputBag($data), new HeaderBag($headers), new InputBag([]), new InputBag([]), [], $method, $uri);
    $response = $router->dispatch($method, $uri, $request);
    ob_start(); $response->send(); $body = (string) ob_get_clean();
    return ['status' => http_response_code(), 'body' => json_decode($body, true, 512, JSON_THROW_ON_ERROR)];
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$connection = $config['connections']['mysql'];
$name = 'directors_resale_platform_operational_admin_' . getmypid();
oa((bool) preg_match('/^directors_resale_platform_operational_admin_[0-9]+$/D', $name), 'Unsafe test database name.');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), (string) $connection['username'], (string) $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
try {
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created = true;
    $config['connections']['mysql']['database'] = $name;
    $database = new DatabaseManager($config); $pdo = $database->connection();
    $migrations = glob($root . '/database/migrations/*.sql') ?: []; sort($migrations); foreach ($migrations as $migration) { $pdo->exec((string) file_get_contents($migration)); }
    $pdo->exec("INSERT INTO organizations (id,name,code,organization_type,status) VALUES (1,'System','SYS','system','active')");
    $pdo->exec("INSERT INTO positions (id,organization_id,name,code,status) VALUES (1,1,'System Onboarder','SYSTEM_ONBOARD','active')");
    $systemPassword = 'system-secret'; $hash = password_hash($systemPassword, PASSWORD_DEFAULT);
    $statement = $pdo->prepare('INSERT INTO users (id,organization_id,position_id,full_name,email,status,password_hash) VALUES (1,1,1,?,?,?,?)');
    $statement->execute(['System Operator', 'operator@example.test', 'active', $hash]);
    $pdo->exec("INSERT INTO position_permissions (position_id, permission_id) SELECT 1,id FROM permissions WHERE code IN ('franchises.create','positions.create','permissions.assign','users.create')");
    $container = new Container(); (new AppServiceProvider($container, []))->register(); $container->instance(DatabaseConnectionInterface::class, $database);
    $router = new Router(); ($routes = require $root . '/routes/api.php')($router, $container, []);
    $login = oaRequest($router, 'POST', '/auth/login', null, ['email' => 'operator@example.test', 'password' => $systemPassword]); oa($login['status'] === 200, 'System login failed.'); $systemToken = $login['body']['data']['token'];
    $payload = ['franchise' => ['name' => 'Demo Franchise','code' => 'DEMO','parent_organization_id' => 1,'status' => 'active'], 'position' => ['name' => 'Franchise Admin','code' => 'ADMIN'], 'administrator' => ['full_name' => 'Demo Administrator','email' => 'admin@demo.test','phone' => null,'password' => 'demo-secret','password_confirmation' => 'demo-secret'], 'permissions' => array_map('intval', $pdo->query("SELECT id FROM permissions WHERE code IN ('properties.view','properties.manage') ORDER BY code")->fetchAll(PDO::FETCH_COLUMN))];
    $onboard = oaRequest($router, 'POST', '/network/franchises/onboard', $systemToken, $payload); oa($onboard['status'] === 201 && $onboard['body']['data']['administrator']['status'] === 'active' && $onboard['body']['data']['activation_status'] === 'active', 'Onboarding did not activate administrator.'); oa(! str_contains(json_encode($onboard['body'], JSON_THROW_ON_ERROR), 'demo-secret') && ! isset($onboard['body']['data']['administrator']['password_hash']), 'Credential leaked in onboarding response.');
    $admin = $pdo->query("SELECT * FROM users WHERE email='admin@demo.test'")->fetch(PDO::FETCH_ASSOC); oa(is_array($admin) && $admin['status'] === 'active' && password_verify('demo-secret', $admin['password_hash']), 'Stored credential is invalid.');
    $adminLogin = oaRequest($router, 'POST', '/auth/login', null, ['email' => 'admin@demo.test','password' => 'demo-secret']); oa($adminLogin['status'] === 200, 'Initial administrator cannot log in.'); $adminToken = $adminLogin['body']['data']['token'];
    $context = oaRequest($router, 'GET', '/auth/context', $adminToken); oa($context['status'] === 200 && $context['body']['data']['organization']['code'] === 'DEMO' && in_array('properties.view', $context['body']['data']['permissions'], true) && in_array('properties.manage', $context['body']['data']['permissions'], true), 'Authentication context lacks franchise property capabilities.');
    oa(oaRequest($router, 'GET', '/organization-properties', $adminToken)['status'] === 200, 'Franchise property list denied.');
    $invalid = $payload; $invalid['franchise']['code'] = 'BAD'; $invalid['administrator']['email'] = 'bad@demo.test'; $invalid['administrator']['password_confirmation'] = 'different'; $failed = oaRequest($router, 'POST', '/network/franchises/onboard', $systemToken, $invalid); oa($failed['status'] === 422 && (int) $pdo->query("SELECT COUNT(*) FROM organizations WHERE code='BAD'")->fetchColumn() === 0, 'Invalid credentials left partial onboarding rows.');
    oa(oaRequest($router, 'POST', '/auth/login', null, ['email' => 'admin@demo.test','password' => 'wrong'])['status'] === 401, 'Invalid login accepted.');    $duplicate = $payload; $duplicate['franchise']['code'] = 'ROLLBACK'; $duplicate['administrator']['email'] = 'admin@demo.test';
    oa(oaRequest($router, 'POST', '/network/franchises/onboard', $systemToken, $duplicate)['status'] === 422 && (int) $pdo->query("SELECT COUNT(*) FROM organizations WHERE code='ROLLBACK'")->fetchColumn() === 0, 'Transactional onboarding failure left partial rows.');
    $limited = $payload; $limited['franchise']['code'] = 'LIMITED'; $limited['franchise']['name'] = 'Limited Franchise'; $limited['position']['code'] = 'LIMITED_ADMIN'; $limited['administrator']['email'] = 'limited@demo.test'; $limited['administrator']['password'] = 'limited-secret'; $limited['administrator']['password_confirmation'] = 'limited-secret'; $limited['permissions'] = [(int) $pdo->query("SELECT id FROM permissions WHERE code='properties.view'")->fetchColumn()];
    oa(oaRequest($router, 'POST', '/network/franchises/onboard', $systemToken, $limited)['status'] === 201, 'Limited onboarding failed.');
    $limitedLogin = oaRequest($router, 'POST', '/auth/login', null, ['email' => 'limited@demo.test','password' => 'limited-secret']); oa($limitedLogin['status'] === 200, 'Limited administrator login failed.');
    oa(oaRequest($router, 'POST', '/organization-properties', $limitedLogin['body']['data']['token'], ['property_label' => 'Denied'])['status'] === 403, 'Missing properties.manage capability was not denied.');
    echo "Operational Franchise Admin Activation: PASS\n";
} finally { if ($created) { $server->exec("DROP DATABASE `$name`"); } }