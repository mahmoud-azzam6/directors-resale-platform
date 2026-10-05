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

function bf016Assert(bool $condition, string $message): void { if (! $condition) throw new RuntimeException($message); }
function bf016Response(Response $response): array {
    foreach (['statusCode' => 'status', 'payload' => 'payload'] as $property => $key) {
        $reflection = new ReflectionProperty(Response::class, $property);
        $reflection->setAccessible(true);
        $result[$key] = $reflection->getValue($response);
    }
    return $result;
}
function bf016Request(Router $router, string $method, string $uri, ?string $token = null, array $data = []): array {
    $request = new Request(new InputBag([]), new InputBag([]), new InputBag($data), new HeaderBag($token === null ? [] : ['Authorization' => 'Bearer ' . $token]), new InputBag([]), new InputBag([]), [], $method, $uri);
    return bf016Response($router->dispatch($method, $uri, $request));
}
function bf016CreateBasicProfileFixture(PDO $testPdo): void {
    $testPdo->exec('CREATE TABLE organization_basic_profiles (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        organization_id BIGINT UNSIGNED NOT NULL,
        geographic_location_id BIGINT UNSIGNED NULL,
        address_text VARCHAR(1000) NULL,
        created_by_user_id BIGINT UNSIGNED NOT NULL,
        updated_by_user_id BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_organization_basic_profiles_organization (organization_id),
        INDEX idx_organization_basic_profiles_geography (geographic_location_id),
        CONSTRAINT chk_organization_basic_profiles_address CHECK (address_text IS NULL OR CHAR_LENGTH(TRIM(address_text)) > 0),
        CONSTRAINT fk_organization_basic_profiles_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
        CONSTRAINT fk_organization_basic_profiles_geography FOREIGN KEY (geographic_location_id) REFERENCES geographic_locations(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
        CONSTRAINT fk_organization_basic_profiles_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
        CONSTRAINT fk_organization_basic_profiles_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$realConfig = require $root . '/config/database.php';
$realConnection = $realConfig['connections']['mysql'];
$realDatabaseName = (string) $realConnection['database'];
$disposableDatabaseName = 'directors_resale_platform_bf016_profile_' . getmypid();
bf016Assert((bool) preg_match('/^directors_resale_platform_bf016_profile_[0-9]+$/D', $disposableDatabaseName), 'Unsafe disposable test database name.');
bf016Assert($disposableDatabaseName !== $realDatabaseName, 'Disposable cleanup target must never be the configured database.');
$serverPdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $realConnection['host'], $realConnection['port']), (string) $realConnection['username'], (string) $realConnection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;

try {
    $serverPdo->exec("CREATE DATABASE `$disposableDatabaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $testConfig = $realConfig;
    $testConfig['connections']['mysql']['database'] = $disposableDatabaseName;
    $database = new DatabaseManager($testConfig);
    $testPdo = $database->connection();
    $migrations = glob($root . '/database/migrations/*.sql') ?: [];
    sort($migrations);
    foreach ($migrations as $migration) {
        if (basename($migration) !== '034_create_organization_basic_profiles_table.sql') {
            $testPdo->exec((string) file_get_contents($migration));
        }
    }
    bf016CreateBasicProfileFixture($testPdo);

    $testPdo->exec("INSERT INTO organizations (id,name,code,organization_type,status) VALUES (1,'System','SYS','system','active'),(2,'Franchise A','FRA','franchise','active'),(3,'Franchise B','FRB','franchise','active')");
    $testPdo->exec("INSERT INTO positions (id,organization_id,name,code,status) VALUES (1,1,'System Admin','SYS_ADMIN','active'),(2,2,'Franchise Admin','FRA_ADMIN','active'),(3,3,'Other Franchise Admin','FRB_ADMIN','active'),(4,2,'No Catalog','FRA_NO_CATALOG','active')");
    $hash = password_hash('profile-secret', PASSWORD_DEFAULT);
    $users = $testPdo->prepare('INSERT INTO users (id,organization_id,position_id,full_name,email,status,password_hash) VALUES (?,?,?,?,?,?,?)');
    foreach ([[1,1,1,'System Admin','system@example.test'],[2,2,2,'Franchise Admin','franchise@example.test'],[3,3,3,'Other Franchise Admin','other@example.test'],[4,2,4,'No Catalog','catalogless@example.test']] as [$id,$organization,$position,$userName,$email]) $users->execute([$id,$organization,$position,$userName,$email,'active',$hash]);
    $tokens = $testPdo->prepare('INSERT INTO auth_tokens (user_id,token_hash,expires_at) VALUES (?,?,?)');
    foreach (['system' => 1, 'franchise' => 2, 'other' => 3, 'catalogless' => 4] as $token => $userId) $tokens->execute([$userId, hash('sha256', $token), '2999-01-01']);
    $testPdo->exec("INSERT INTO position_permissions (position_id,permission_id) SELECT p.id,q.id FROM positions p JOIN permissions q WHERE p.id IN (1,2,3) AND q.code IN ('franchises.create','positions.create','permissions.assign','users.create','organizations.view','organizations.update','property_catalogs.view')");
    $testPdo->exec("INSERT INTO position_permissions (position_id,permission_id) SELECT 4,id FROM permissions WHERE code IN ('organizations.view','organizations.update')");
    $testPdo->exec("INSERT INTO geographic_locations (id,ulid,parent_id,code,name_ar,name_en,status,provenance,type,sort_order) VALUES (10,'00000000000000000000000010',NULL,'TEST_COUNTRY','Test Country','Test Country','active','SYSTEM_ADMIN','COUNTRY',0),(11,'00000000000000000000000011',10,'TEST_GOVERNORATE','Test Governorate','Test Governorate','active','SYSTEM_ADMIN','GOVERNORATE',0),(12,'00000000000000000000000012',11,'TEST_CITY','Test City','Test City','active','SYSTEM_ADMIN','CITY',0),(13,'00000000000000000000000013',11,'TEST_INACTIVE','Inactive','Inactive','inactive','SYSTEM_ADMIN','CITY',1),(14,'00000000000000000000000014',12,'TEST_WRONG','Wrong Hierarchy','Wrong Hierarchy','active','SYSTEM_ADMIN','GOVERNORATE',0)");

    $container = new Container();
    (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $database);
    $router = new Router();
    ($routes = require $root . '/routes/api.php')($router, $container, []);

    $onboard = ['franchise' => ['name' => 'No Profile','code' => 'NOP','parent_organization_id' => 1,'status' => 'active'],'position' => ['name' => 'Admin','code' => 'NOP_ADMIN'],'administrator' => ['full_name' => 'No Profile Admin','email' => 'nop@example.test','phone' => null,'password' => 'onboard-secret','password_confirmation' => 'onboard-secret'],'permissions' => [(int) $testPdo->query("SELECT id FROM permissions WHERE code='organizations.view'")->fetchColumn()]];
    $createdWithoutProfile = bf016Request($router, 'POST', '/network/franchises/onboard', 'system', $onboard);
    bf016Assert($createdWithoutProfile['status'] === 201 && $createdWithoutProfile['payload']['data']['basic_profile'] === null, 'Onboarding without a basic profile must succeed.');
    bf016Assert((int) $testPdo->query("SELECT COUNT(*) FROM organization_basic_profiles WHERE organization_id=" . (int) $createdWithoutProfile['payload']['data']['franchise']['id'])->fetchColumn() === 0, 'Empty onboarding must not create a profile row.');
    bf016Assert(! str_contains(json_encode($createdWithoutProfile['payload'], JSON_THROW_ON_ERROR), 'onboard-secret') && ! str_contains(json_encode($createdWithoutProfile['payload'], JSON_THROW_ON_ERROR), 'password_hash'), 'Onboarding response leaked a password or hash.');

    $withProfile = $onboard;
    $withProfile['franchise'] = ['name' => 'With Profile','code' => 'WPR','parent_organization_id' => 1,'status' => 'active'];
    $withProfile['position']['code'] = 'WPR_ADMIN';
    $withProfile['administrator']['email'] = 'wpr@example.test';
    $withProfile['basic_profile'] = ['geographic_location_id' => 12, 'address_text' => '  Muscat address  '];
    $createdWithProfile = bf016Request($router, 'POST', '/network/franchises/onboard', 'system', $withProfile);
    bf016Assert($createdWithProfile['status'] === 201 && $createdWithProfile['payload']['data']['basic_profile']['geographic_location_id'] === 12 && $createdWithProfile['payload']['data']['basic_profile']['address_text'] === 'Muscat address', 'Onboarding with a valid profile failed.');
    bf016Assert(! array_key_exists('token', $createdWithProfile['payload']['data']['administrator']) && ! str_contains(json_encode($createdWithProfile['payload'], JSON_THROW_ON_ERROR), 'private_path'), 'Onboarding response leaked a token or private path.');

    bf016Assert(bf016Request($router, 'GET', '/organizations/2/basic-profile')['status'] === 401, 'Unauthenticated profile read must be denied.');
    $ownEmpty = bf016Request($router, 'GET', '/organizations/2/basic-profile', 'franchise');
    bf016Assert($ownEmpty['status'] === 200 && $ownEmpty['payload']['data'] === ['profile' => null], 'Franchise Admin must read its own empty profile with the canonical envelope.');
    $ownUpdate = bf016Request($router, 'PUT', '/organizations/2/basic-profile', 'franchise', ['geographic_location_id' => 12, 'address_text' => 'Franchise address']);
    bf016Assert($ownUpdate['status'] === 200 && $ownUpdate['payload']['data']['profile']['address_text'] === 'Franchise address', 'Franchise Admin must update its own profile.');
    bf016Assert(bf016Request($router, 'GET', '/organizations/2/basic-profile', 'system')['status'] === 200 && bf016Request($router, 'PUT', '/organizations/2/basic-profile', 'system', ['geographic_location_id' => 10, 'address_text' => 'System update'])['status'] === 200, 'System Admin must read and update Franchise profiles.');
    bf016Assert(bf016Request($router, 'GET', '/organizations/3/basic-profile', 'franchise')['status'] === 403 && bf016Request($router, 'PUT', '/organizations/3/basic-profile', 'franchise', ['address_text' => 'Denied'])['status'] === 403, 'PRIVATE_ORGANIZATION must deny cross-Organization profile access.');
    bf016Assert(bf016Request($router, 'GET', '/geographic-locations', 'catalogless')['status'] === 403, 'Missing property_catalogs.view must deny geography reads.');

    foreach ([[999999, 'missing'], [13, 'inactive'], [14, 'wrong hierarchy']] as [$location, $label]) {
        $result = bf016Request($router, 'PUT', '/organizations/2/basic-profile', 'franchise', ['geographic_location_id' => $location, 'address_text' => 'Rejected']);
        bf016Assert($result['status'] === 422, "The $label geographic location must be rejected.");
    }
    $rollback = $withProfile;
    $rollback['franchise'] = ['name' => 'Rollback Profile','code' => 'RBP','parent_organization_id' => 1,'status' => 'active'];
    $rollback['position']['code'] = 'RBP_ADMIN';
    $rollback['administrator']['email'] = 'rollback@example.test';
    $rollback['basic_profile'] = ['geographic_location_id' => 13, 'address_text' => 'Invalid'];
    bf016Assert(bf016Request($router, 'POST', '/network/franchises/onboard', 'system', $rollback)['status'] === 422 && (int) $testPdo->query("SELECT COUNT(*) FROM organizations WHERE code='RBP'")->fetchColumn() === 0, 'Invalid profile onboarding must roll back all onboarding records.');
    echo "BF016 Organization Basic Profile HTTP MariaDB acceptance: PASS\n";
} finally {
    if ($created) {
        $cleanupTarget = $disposableDatabaseName;
        bf016Assert((bool) preg_match('/^directors_resale_platform_bf016_profile_[0-9]+$/D', $cleanupTarget) && $cleanupTarget !== $realDatabaseName, 'Cleanup target must be the disposable BF016 database only.');
        $serverPdo->exec("DROP DATABASE `$cleanupTarget`");
    }
}
