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

function bf014ReadAssert(bool $condition, string $message): void { if (! $condition) { throw new RuntimeException($message); } }
function bf014ReadResult(Response $response): array { foreach (['statusCode' => 'status', 'payload' => 'payload'] as $property => $key) { $reflection = new ReflectionProperty(Response::class, $property); $reflection->setAccessible(true); $result[$key] = $reflection->getValue($response); } return $result; }
function bf014ReadDispatch(Router $router, string $uri, ?string $token = null, array $query = []): array { $request = new Request(new InputBag($query), new InputBag([]), new InputBag([]), new HeaderBag($token === null ? [] : ['Authorization' => "Bearer {$token}"]), new InputBag([]), new InputBag([]), [], 'GET', $uri); return bf014ReadResult($router->dispatch('GET', $uri, $request)); }

$root = dirname(__DIR__, 2); Dotenv::createImmutable($root)->safeLoad(); $config = require $root . '/config/database.php'; $connection = $config['connections']['mysql']; $development = (string) $connection['database']; $name = 'directors_resale_platform_bf014_catalog_read_test_' . getmypid(); $safe = static fn (string $value): bool => preg_match('/^directors_resale_platform_bf014_catalog_read_test_[0-9]+$/D', $value) === 1;
bf014ReadAssert($safe($name) && $name !== $development, 'Unsafe test database.'); $server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), (string) $connection['username'], (string) $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); $created = false;
try {
    $server->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created = true; $config['connections']['mysql']['database'] = $name; $database = new DatabaseManager($config); $pdo = $database->connection(); foreach (glob($root . '/database/migrations/*.sql') ?: [] as $migration) { $pdo->exec((string) file_get_contents($migration)); }
    $pdo->exec("INSERT INTO organizations (id,name,code,organization_type,status) VALUES (1,'System','SYS','system','active')"); $pdo->exec("INSERT INTO positions (id,organization_id,name,code,status) VALUES (1,1,'Viewer','VIEWER','active'),(2,1,'Manager','MANAGER','active'),(3,1,'None','NONE','active')"); $hash = password_hash('test', PASSWORD_DEFAULT); $insert = $pdo->prepare('INSERT INTO users (id,organization_id,full_name,email,status,password_hash,position_id) VALUES (?,?,?,?,?,?,?)'); foreach ([[1,'Viewer','viewer@test','active',$hash,1],[2,'Manager','manager@test','active',$hash,2],[3,'None','none@test','active',$hash,3]] as [$id,$full,$email,$status,$password,$position]) { $insert->execute([$id,1,$full,$email,$status,$password,$position]); }
    $tokens = $pdo->prepare('INSERT INTO auth_tokens (user_id,token_hash,expires_at) VALUES (?,?,?)'); foreach (['viewer'=>1,'manager'=>2,'none'=>3] as $token => $user) { $tokens->execute([$user,hash('sha256',$token),'2999-01-01 00:00:00']); } $pdo->exec("INSERT INTO position_permissions (position_id,permission_id) SELECT 1,id FROM permissions WHERE code='property_catalogs.view'"); $pdo->exec("INSERT INTO position_permissions (position_id,permission_id) SELECT 2,id FROM permissions WHERE code='property_catalogs.manage'");
    $pdo->exec("INSERT INTO property_categories (id,ulid,code,name_ar,name_en,status,provenance,sort_order) VALUES (1,'00000000000000000000000001','RES','a','Residential','active','SYSTEM_ADMIN',1),(2,'00000000000000000000000002','COM','b','Commercial','inactive','SYSTEM_ADMIN',2)");
    $pdo->exec("INSERT INTO unit_types (id,ulid,property_category_id,code,name_ar,name_en,status,provenance,sort_order) VALUES (1,'00000000000000000000000003',1,'APT','a','Apartment','active','SYSTEM_ADMIN',1),(2,'00000000000000000000000004',2,'OFF','b','Office','active','SYSTEM_ADMIN',2)");
    $pdo->exec("INSERT INTO measurement_definitions (id,ulid,code,name_ar,name_en,status,provenance,default_unit_code,sort_order) VALUES (1,'00000000000000000000000005','AREA','a','Area','active','SYSTEM_ADMIN','SQM',1)");
    $pdo->exec("INSERT INTO attribute_definitions (id,ulid,code,name_ar,name_en,status,provenance,data_type,sort_order) VALUES (1,'00000000000000000000000006','FURNISHING','a','Furnishing','active','SYSTEM_ADMIN','ENUM',1)");
    $pdo->exec("INSERT INTO attribute_options (id,ulid,attribute_definition_id,code,name_ar,name_en,status,provenance,sort_order) VALUES (1,'00000000000000000000000007',1,'YES','a','Yes','active','SYSTEM_ADMIN',1)");
    $container = new Container(); (new AppServiceProvider($container, []))->register(); $container->instance(DatabaseConnectionInterface::class, $database); $router = new Router(); $routes = require $root . '/routes/api.php'; $routes($router, $container, ['app'=>['version'=>'test']]);
    foreach (['/property-categories','/property-categories/1'] as $uri) { bf014ReadAssert(bf014ReadDispatch($router,$uri)['status']===401, "{$uri} must require authentication."); }
    bf014ReadAssert(bf014ReadDispatch($router,'/property-categories','none')['status']===403, 'Missing view capability must be forbidden.'); bf014ReadAssert(bf014ReadDispatch($router,'/property-categories','manager')['status']===403, 'Manage must not imply view.');
    $categories = bf014ReadDispatch($router,'/property-categories','viewer',['status'=>'active','search'=>'resi','limit'=>'1','offset'=>'0']); bf014ReadAssert($categories['status']===200 && count($categories['payload']['data'])===1, 'Authorized category filters must pass through.');
    bf014ReadAssert(bf014ReadDispatch($router,'/property-categories/1','viewer')['status']===200, 'Authorized category item must succeed.'); bf014ReadAssert(bf014ReadDispatch($router,'/property-categories/999','viewer')['status']===404, 'Missing category must return 404.');
    $units=bf014ReadDispatch($router,'/unit-types','viewer',['property_category_id'=>'1']); bf014ReadAssert($units['status']===200 && count($units['payload']['data'])===1 && $units['payload']['data'][0]['code']==='APT','Category filtering must pass through.');
    foreach (['/unit-types/1','/measurement-definitions','/measurement-definitions/1','/attribute-definitions','/attribute-definitions/1'] as $uri) { bf014ReadAssert(bf014ReadDispatch($router,$uri,'viewer')['status']===200, "{$uri} must be readable."); }
    $options=bf014ReadDispatch($router,'/attribute-definitions/1/options','viewer'); bf014ReadAssert($options['status']===200 && count($options['payload']['data']['options'])===1, 'Scoped attribute options must be readable.'); bf014ReadAssert(bf014ReadDispatch($router,'/attribute-definitions/999/options','viewer')['status']===404, 'Missing option parent must return 404.');
    foreach (['GET /geographic-locations'] as $forbidden) { [$method,$uri] = explode(' ', $forbidden, 2); $request = new Request(new InputBag([]),new InputBag([]),new InputBag([]),new HeaderBag(['Authorization'=>'Bearer viewer']),new InputBag([]),new InputBag([]),[],$method,$uri); bf014ReadAssert(bf014ReadResult($router->dispatch($method,$uri,$request))['status']===404, "{$forbidden} must not be registered by B1/B2/C1."); }
    $source=file_get_contents($root.'/app/Modules/Property/Controllers/PropertyCatalogReadController.php'); bf014ReadAssert(is_string($source) && !str_contains($source,'Repository'), 'Read controller must not call repositories.'); echo "BF014 catalog read HTTP integration: PASS\n";
} finally { if ($created) { bf014ReadAssert($safe($name) && $name !== $development,'Unsafe cleanup.'); $server->exec("DROP DATABASE `{$name}`"); } }
