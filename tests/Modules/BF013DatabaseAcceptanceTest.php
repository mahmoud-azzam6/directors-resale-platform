<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Http\HeaderBag;
use App\Http\InputBag;
use App\Http\Request;
use App\Modules\Owner\Repositories\OwnerRepository;
use App\Modules\Ownership\Repositories\AuthorizedActingOwnerDesignationRepository;
use App\Modules\Ownership\Repositories\OwnershipPartyRepository;
use App\Modules\Ownership\Repositories\OwnershipRepository;
use App\Modules\Ownership\Services\OwnershipService;
use App\Modules\Property\Repositories\OrganizationPropertyRepository;
use App\Providers\AppServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Responses\Response;
use App\Routing\Router;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function bf013DbAssert(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
}

/** @return array{status: int, payload: array<string, mixed>} */
function bf013DbResponse(Response $response): array
{
    $status = new ReflectionProperty(Response::class, 'statusCode');
    $payload = new ReflectionProperty(Response::class, 'payload');
    $status->setAccessible(true);
    $payload->setAccessible(true);
    return ['status' => $status->getValue($response), 'payload' => $payload->getValue($response)];
}

function bf013DbRequest(string $method, string $uri, ?string $token = null, array $query = [], array $json = []): Request
{
    return new Request(
        new InputBag($query), new InputBag([]), new InputBag($json),
        new HeaderBag($token === null ? [] : ['Authorization' => "Bearer {$token}"]),
        new InputBag([]), new InputBag([]), [], $method, $uri
    );
}

/** @return array{status: int, payload: array<string, mixed>, request: Request} */
function bf013DbDispatch(Router $router, string $method, string $uri, ?string $token = null, array $query = [], array $json = []): array
{
    $request = bf013DbRequest($method, $uri, $token, $query, $json);
    $result = bf013DbResponse($router->dispatch($method, $uri, $request));
    $result['request'] = $request;
    return $result;
}

function bf013DbExpectConstraint(PDO $pdo, callable $operation, string $message): void
{
    try { $operation(); }
    catch (PDOException) { return; }
    throw new RuntimeException($message);
}

function bf013DbId(array $result): int
{
    return (int) ($result['payload']['data']['id'] ?? 0);
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$normalConfig = require $root . '/config/database.php';
$connection = $normalConfig['connections']['mysql'];
$developmentDatabase = (string) $connection['database'];
$databaseName = 'directors_resale_platform_bf013_test_' . getmypid();
$safeName = static fn (string $name): bool => preg_match('/^directors_resale_platform_bf013_test_[0-9]+$/D', $name) === 1;

bf013DbAssert($safeName($databaseName), 'Generated acceptance database name failed the safety policy.');
bf013DbAssert($databaseName !== $developmentDatabase, 'Acceptance database must differ from the configured development database.');

$serverDsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']);
$server = new PDO($serverDsn, (string) $connection['username'], (string) $connection['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$created = false;
$cleaned = false;
$cleanup = static function () use ($server, $databaseName, $developmentDatabase, $safeName, &$created, &$cleaned): void {
    if (! $created || $cleaned) { return; }
    if (! $safeName($databaseName) || $databaseName === $developmentDatabase) {
        throw new RuntimeException('Unsafe database cleanup was refused.');
    }
    $server->exec("DROP DATABASE IF EXISTS `{$databaseName}`");
    $statement = $server->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = ?');
    $statement->execute([$databaseName]);
    bf013DbAssert((int) $statement->fetchColumn() === 0, 'Acceptance database cleanup could not be verified.');
    $cleaned = true;
};
register_shutdown_function(static function () use ($cleanup): void { $cleanup(); });

try {
    $server->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $testConfig = $normalConfig;
    $testConfig['connections']['mysql']['database'] = $databaseName;
    $database = new DatabaseManager($testConfig);
    $pdo = $database->connection();

    $migrationFiles = glob($root . '/database/migrations/*.sql') ?: [];
    sort($migrationFiles, SORT_STRING);
    $actualMigrations = array_map('basename', $migrationFiles);
    $expectedMigrations = [
        '001_create_organizations_table.sql', '002_add_parent_organization_id_to_organizations_table.sql',
        '003_create_users_table.sql', '004_add_password_hash_to_users_table.sql',
        '005_create_auth_tokens_table.sql', '006_create_positions_table.sql',
        '007_add_position_id_to_users_table.sql', '008_create_permissions_tables.sql',
        '009_create_organization_properties_table.sql', '010_create_owners_table.sql',
        '011_create_ownerships_table.sql', '012_create_ownership_parties_table.sql',
        '013_create_authorized_acting_owner_designations_table.sql',
        '014_create_global_physical_property_identities_table.sql',
        '015_create_global_physical_identity_links_table.sql',
        '016_create_property_owner_lifecycle_history_table.sql', '017_add_bf013_permissions.sql',
    ];
    bf013DbAssert($actualMigrations === $expectedMigrations, 'Migrations 001-017 were not found in the exact expected order.');
    foreach ($migrationFiles as $migrationFile) {
        $sql = file_get_contents($migrationFile);
        bf013DbAssert(is_string($sql) && trim($sql) !== '', basename($migrationFile) . ' is empty or unreadable.');
        $pdo->exec($sql);
    }
    bf013DbAssert((int) $pdo->query("SELECT COUNT(*) FROM permissions WHERE code = 'permissions.assign'")->fetchColumn() === 1, 'Migration 008 multi-statement execution failed.');
    bf013DbAssert((int) $pdo->query("SELECT COUNT(*) FROM permissions WHERE code LIKE 'global_physical_identities.%'")->fetchColumn() === 2, 'Migration 017 permission data was not applied.');

    $pdo->exec("INSERT INTO organizations (id,name,code,organization_type,status,parent_organization_id) VALUES
        (1,'System','SYS','system','active',NULL),(2,'Franchise A','FA','franchise','active',1),
        (3,'Partner A','PA','partner_agency','active',2),(4,'Franchise B','FB','franchise','active',1),
        (5,'Partner B','PB','partner_agency','active',4)");
    $pdo->exec("INSERT INTO positions (id,organization_id,name,code,status) VALUES
        (10,1,'System Admin','system-admin','active'),(11,1,'System Limited','system-limited','active'),
        (20,2,'Franchise Admin','franchise-admin','active'),(30,3,'Partner Admin','partner-admin','active')");
    $passwordHash = password_hash('BF013-test-password', PASSWORD_DEFAULT);
    $userInsert = $pdo->prepare('INSERT INTO users (id,organization_id,full_name,email,phone,status,password_hash,position_id) VALUES (?,?,?,?,?,?,?,?)');
    foreach ([[1,1,'System Admin','bf013-system@example.test',null,'active',$passwordHash,10],[2,1,'System Limited','bf013-limited@example.test',null,'active',$passwordHash,11],[3,2,'Franchise Admin','bf013-franchise@example.test',null,'active',$passwordHash,20],[4,3,'Partner Admin','bf013-partner@example.test',null,'active',$passwordHash,30]] as $row) { $userInsert->execute($row); }
    $tokenInsert = $pdo->prepare('INSERT INTO auth_tokens (user_id,token_hash,expires_at,revoked_at) VALUES (?,?,?,NULL)');
    foreach (['system' => 1, 'limited' => 2, 'franchise' => 3, 'partner' => 4] as $token => $userId) { $tokenInsert->execute([$userId, hash('sha256', "bf013-db-{$token}"), '2999-01-01 00:00:00']); }
    $pdo->exec('INSERT INTO position_permissions (position_id,permission_id) SELECT 10,id FROM permissions');
    $pdo->exec("INSERT INTO position_permissions (position_id,permission_id) SELECT 20,id FROM permissions WHERE code IN ('properties.view','properties.manage','owners.view','owners.manage','ownerships.view','ownerships.manage','global_physical_identities.view','global_physical_identities.manage')");
    $pdo->exec("INSERT INTO position_permissions (position_id,permission_id) SELECT 30,id FROM permissions WHERE code IN ('properties.view','properties.manage','owners.view','owners.manage','ownerships.view','ownerships.manage','global_physical_identities.view','global_physical_identities.manage')");

    $config = ['app' => ['name' => 'BF013 Acceptance', 'version' => 'test', 'timezone' => 'UTC', 'log' => ['path' => sys_get_temp_dir() . '/bf013-acceptance.log', 'level' => 400], 'auth' => ['token_ttl' => 3600]], 'database' => $testConfig];
    $container = new Container();
    (new AppServiceProvider($container, $config))->register();
    (new RouteServiceProvider($container, $config))->register();
    $router = $container->make(Router::class);
    $system = 'bf013-db-system'; $limited = 'bf013-db-limited'; $franchise = 'bf013-db-franchise'; $partner = 'bf013-db-partner';

    bf013DbAssert(bf013DbDispatch($router, 'GET', '/organization-properties')['status'] === 401, 'Unauthenticated BF013 route must return 401.');
    $property = bf013DbDispatch($router, 'POST', '/organization-properties', $partner, [], ['property_label' => 'Partner Property']);
    bf013DbAssert($property['status'] === 201 && $property['payload']['data']['organization_id'] === 3, 'Partner Property create failed.');
    $propertyId = bf013DbId($property);
    $childPropertyRequest = bf013DbDispatch($router, 'POST', '/organization-properties', $franchise, [], ['organization_id' => 3, 'property_label' => 'Franchise Managed Child Property']);
    bf013DbAssert($childPropertyRequest['status'] === 201 && $childPropertyRequest['request']->attribute('auth.target.organization_id') === 3, 'Authorized child Property target was not trusted/persisted.');
    $childPropertyId = bf013DbId($childPropertyRequest);
    $denied = bf013DbDispatch($router, 'POST', '/organization-properties', $franchise, [], ['organization_id' => 5, 'property_label' => 'Denied']);
    bf013DbAssert($denied['status'] === 403 && $denied['request']->attribute('auth.target.organization_id') === null, 'Out-of-scope target must return 403 and remain untrusted.');
    $invalid = bf013DbDispatch($router, 'POST', '/organization-properties', $franchise, [], ['organization_id' => 'bad', 'property_label' => 'Invalid']);
    bf013DbAssert($invalid['status'] === 422 && $invalid['request']->attribute('auth.target.organization_id') === null, 'Invalid target must return 422 and remain untrusted.');
    $list = bf013DbDispatch($router, 'GET', '/organization-properties', $franchise);
    bf013DbAssert($list['status'] === 200 && $list['request']->attribute('auth.scope.organization_ids') === [2,3] && $list['request']->attribute('auth.target.organization_id') === null, 'Hierarchy list scope/trusted target failed.');
    bf013DbAssert(count($list['payload']['data']) === 2, 'Hierarchy Property list returned unauthorized rows.');
    bf013DbAssert(bf013DbDispatch($router, 'PUT', "/organization-properties/{$propertyId}", $partner, [], ['property_label' => 'Updated Partner Property'])['payload']['data']['property_label'] === 'Updated Partner Property', 'Property update did not persist.');
    bf013DbAssert(bf013DbDispatch($router, 'DELETE', "/organization-properties/{$propertyId}", $partner)['payload']['data']['status'] === 'archived', 'Property archive did not persist.');
    bf013DbAssert(bf013DbDispatch($router, 'POST', "/organization-properties/{$propertyId}/reactivate", $partner)['payload']['data']['status'] === 'active', 'Property reactivation did not persist.');
    $propertyHistory = $pdo->query("SELECT action,from_status,to_status,created_by_user_id FROM property_owner_lifecycle_history WHERE organization_property_id={$propertyId} ORDER BY id")->fetchAll();
    bf013DbAssert(array_column($propertyHistory, 'action') === ['property_archive','property_reactivate'] && (int) $propertyHistory[0]['created_by_user_id'] === 4, 'Property lifecycle history is incorrect.');
    $propertyRead = bf013DbDispatch($router, 'GET', "/organization-properties/{$propertyId}", $franchise);
    foreach (['owners','ownership','acting_owner','global_physical_identity','network_match'] as $field) { bf013DbAssert(! array_key_exists($field, $propertyRead['payload']['data']), "Property response leaked {$field}."); }
    $missing = bf013DbDispatch($router, 'GET', '/organization-properties/999999', $system);
    bf013DbAssert($missing['status'] === 404 && $missing['request']->attribute('auth.target.organization_id') === null, 'Missing Property target semantics failed.');

    $owner1 = bf013DbDispatch($router, 'POST', '/owners', $partner, [], ['party_type' => 'individual', 'display_name' => 'Partner Owner One', 'mobile' => '111']);
    $owner2 = bf013DbDispatch($router, 'POST', '/owners', $partner, [], ['party_type' => 'individual', 'display_name' => 'Partner Owner Two']);
    $owner3 = bf013DbDispatch($router, 'POST', '/owners', $partner, [], ['party_type' => 'individual', 'display_name' => 'Partner Owner Three']);
    bf013DbAssert($owner1['status'] === 201 && $owner1['payload']['data']['organization_id'] === 3, 'Partner Owner create failed.');
    $owner1Id=bf013DbId($owner1); $owner2Id=bf013DbId($owner2); $owner3Id=bf013DbId($owner3);
    bf013DbAssert(bf013DbDispatch($router, 'GET', "/owners/{$owner1Id}", $franchise)['status'] === 403, 'Franchise accessed child private Owner.');
    bf013DbAssert(bf013DbDispatch($router, 'GET', "/owners/{$owner1Id}", $system)['status'] === 200, 'System explicit Owner target failed.');
    bf013DbAssert(bf013DbDispatch($router, 'GET', '/owners/999999', $system)['status'] === 404, 'Missing Owner target must return 404.');
    $ownerSearch=bf013DbDispatch($router, 'GET', '/owners', $partner, ['display_name' => 'Partner Owner']);
    bf013DbAssert($ownerSearch['status'] === 200 && array_unique(array_column($ownerSearch['payload']['data'],'organization_id')) === [3], 'Private Owner search escaped its Organization.');
    bf013DbAssert(bf013DbDispatch($router, 'GET', '/owners', $system, ['display_name' => 'Owner'])['status'] === 422, 'Ambiguous System Owner search must return 422.');
    bf013DbAssert(bf013DbDispatch($router, 'DELETE', "/owners/{$owner1Id}", $partner)['payload']['data']['status'] === 'inactive', 'Owner deactivation failed.');
    bf013DbAssert(bf013DbDispatch($router, 'POST', "/owners/{$owner1Id}/reactivate", $partner)['payload']['data']['status'] === 'active', 'Owner reactivation failed.');
    bf013DbAssert((int) $pdo->query("SELECT COUNT(*) FROM property_owner_lifecycle_history WHERE owner_id={$owner1Id}")->fetchColumn() === 2, 'Owner lifecycle history did not persist.');
    bf013DbExpectConstraint($pdo, fn () => $pdo->exec("INSERT INTO owners (ulid,organization_id,party_type,display_name,preferred_contact_method,status,created_by_user_id,updated_by_user_id) VALUES ('00000000000000000000000001',3,'individual','Invalid','fax','active',4,4)"), 'preferred_contact_method CHECK was not enforced.');

    $ownership = bf013DbDispatch($router, 'POST', "/organization-properties/{$propertyId}/ownerships", $partner, [], ['parties' => [['owner_id'=>$owner1Id,'share_percentage'=>null],['owner_id'=>$owner2Id,'share_percentage'=>'60']]]);
    bf013DbAssert($ownership['status'] === 201 && count($ownership['payload']['data']['parties']) === 2, 'Current Ownership creation failed.');
    $ownershipId=bf013DbId($ownership); $party1Id=(int)$ownership['payload']['data']['parties'][0]['id']; $party2Id=(int)$ownership['payload']['data']['parties'][1]['id'];
    bf013DbAssert(bf013DbDispatch($router, 'POST', "/organization-properties/{$propertyId}/ownerships", $partner, [], ['parties'=>[['owner_id'=>$owner1Id,'share_percentage'=>'100']]])['status'] === 422, 'Service did not reject a second current Ownership.');
    bf013DbExpectConstraint($pdo, fn () => $pdo->exec("INSERT INTO ownerships (ulid,organization_property_id,organization_id,status,created_by_user_id,updated_by_user_id) VALUES ('00000000000000000000000002',{$propertyId},3,'current',4,4)"), 'Current Ownership generated guard was not enforced.');
    $partyAdd=bf013DbDispatch($router,'POST',"/ownerships/{$ownershipId}/parties",$partner,[],['owner_id'=>$owner3Id,'share_percentage'=>'20']);
    bf013DbAssert($partyAdd['status']===200,'Ownership Party add failed.'); $party3Id=bf013DbId($partyAdd);
    bf013DbAssert(bf013DbDispatch($router,'POST',"/ownerships/{$ownershipId}/parties",$partner,[],['owner_id'=>$owner3Id,'share_percentage'=>null])['status']===422,'Duplicate Owner Party was not rejected.');
    bf013DbAssert(bf013DbDispatch($router,'PUT',"/ownerships/{$ownershipId}/parties/{$party1Id}",$partner,[],['share_percentage'=>'30'])['status']===422,'Known total above 100 was not rejected.');
    bf013DbAssert(bf013DbDispatch($router,'PUT',"/ownerships/{$ownershipId}/parties/{$party1Id}",$partner,[],['share_percentage'=>'20'])['status']===200,'Exact 100 percent share set failed.');
    bf013DbAssert(bf013DbDispatch($router,'PUT',"/ownerships/{$ownershipId}/parties/{$party1Id}",$partner,[],['share_percentage'=>'0'])['status']===422,'Invalid share lower bound was not rejected.');
    $ownerB = $pdo->prepare("INSERT INTO owners (ulid,organization_id,party_type,display_name,status,created_by_user_id,updated_by_user_id) VALUES ('00000000000000000000000003',5,'individual','Other Owner','active',1,1)"); $ownerB->execute(); $ownerBId=(int)$pdo->lastInsertId();
    bf013DbAssert(bf013DbDispatch($router,'POST',"/ownerships/{$ownershipId}/parties",$partner,[],['owner_id'=>$ownerBId,'share_percentage'=>null])['status']===422,'Cross-Organization Owner Party was not rejected.');
    bf013DbExpectConstraint($pdo, fn () => $pdo->exec("INSERT INTO ownership_parties (ulid,ownership_id,owner_id,organization_id,share_percentage,created_by_user_id,updated_by_user_id) VALUES ('00000000000000000000000004',{$ownershipId},{$ownerBId},3,10,4,4)"), 'Ownership Party composite Organization FK was not enforced.');
    bf013DbExpectConstraint($pdo, fn () => $pdo->exec("INSERT INTO ownership_parties (ulid,ownership_id,owner_id,organization_id,share_percentage,created_by_user_id,updated_by_user_id) VALUES ('00000000000000000000000005',{$ownershipId},{$owner1Id},3,10,4,4)"), 'Duplicate Owner Party uniqueness was not enforced.');
    $pdo->exec("INSERT INTO owners (ulid,organization_id,party_type,display_name,status,created_by_user_id,updated_by_user_id) VALUES ('00000000000000000000000006',3,'individual','Share Low Probe','active',4,4),('00000000000000000000000007',3,'individual','Share High Probe','active',4,4)");
    $shareProbeIds=$pdo->query("SELECT id FROM owners WHERE display_name LIKE 'Share % Probe' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    foreach ([['0',(int)$shareProbeIds[0],'00000000000000000000000012'],['100.0001',(int)$shareProbeIds[1],'00000000000000000000000013']] as [$share,$shareOwnerId,$shareUlid]) { bf013DbExpectConstraint($pdo, fn () => $pdo->exec("INSERT INTO ownership_parties (ulid,ownership_id,owner_id,organization_id,share_percentage,created_by_user_id,updated_by_user_id) VALUES ('{$shareUlid}',{$ownershipId},{$shareOwnerId},3,{$share},4,4)"), 'Ownership Party share CHECK was not enforced.'); }

    bf013DbAssert(bf013DbDispatch($router,'POST',"/ownerships/{$ownershipId}/acting-owner",$partner,[],['ownership_party_id'=>999999,'basis_source'=>'invalid'])['status']===422,'Invalid Acting Owner Party relation was not rejected.');
    $designation=bf013DbDispatch($router,'POST',"/ownerships/{$ownershipId}/acting-owner",$partner,[],['ownership_party_id'=>$party1Id,'basis_source'=>'agreement']);
    bf013DbAssert($designation['status']===200,'Acting Owner designation failed.'); $designation1Id=bf013DbId($designation);
    $changed=bf013DbDispatch($router,'PUT',"/ownerships/{$ownershipId}/acting-owner",$partner,[],['ownership_party_id'=>$party2Id,'basis_source'=>'agreement']);
    bf013DbAssert($changed['status']===200,'Acting Owner change failed.'); $designation2Id=bf013DbId($changed);
    bf013DbAssert($pdo->query("SELECT ended_at IS NOT NULL FROM authorized_acting_owner_designations WHERE id={$designation1Id}")->fetchColumn()==1,'Previous exact designation was not ended.');
    bf013DbExpectConstraint($pdo, fn () => $pdo->exec("INSERT INTO authorized_acting_owner_designations (ulid,ownership_id,ownership_party_id,basis_source,started_at,created_by_user_id,updated_by_user_id) VALUES ('00000000000000000000000008',{$ownershipId},{$party1Id},'duplicate',NOW(),4,4)"), 'Current Acting Owner generated guard was not enforced.');

    $currentUlid=(string)$pdo->query("SELECT ulid FROM authorized_acting_owner_designations WHERE id={$designation2Id}")->fetchColumn();
    $fixedGenerator=new class($currentUlid) implements UlidGeneratorInterface { public function __construct(private string $value){} public function generate(): string{return $this->value;} };
    $rollbackService=new OwnershipService($container->make(OwnershipRepository::class),$container->make(OwnershipPartyRepository::class),$container->make(AuthorizedActingOwnerDesignationRepository::class),$container->make(OrganizationPropertyRepository::class),$container->make(OwnerRepository::class),$container->make(DatabaseConnectionInterface::class),$fixedGenerator);
    try { $rollbackService->changeActingOwner($ownershipId,3,['ownership_party_id'=>$party3Id,'basis_source'=>'rollback'],4); throw new RuntimeException('Rollback collision did not fail.'); } catch (PDOException) {}
    $rolledBack=$pdo->query("SELECT ended_at,ended_by_user_id FROM authorized_acting_owner_designations WHERE id={$designation2Id}")->fetch();
    bf013DbAssert($rolledBack['ended_at']===null && $rolledBack['ended_by_user_id']===null && (int)$pdo->query("SELECT COUNT(*) FROM authorized_acting_owner_designations WHERE ownership_id={$ownershipId} AND ended_at IS NULL")->fetchColumn()===1,'Post-write designation failure did not rollback.');
    bf013DbAssert(bf013DbDispatch($router,'DELETE',"/ownerships/{$ownershipId}/acting-owner",$partner)['status']===200,'Acting Owner clear failed.');
    bf013DbAssert(bf013DbDispatch($router,'POST',"/ownerships/{$ownershipId}/acting-owner",$partner,[],['ownership_party_id'=>$party2Id,'basis_source'=>'close'])['status']===200,'Pre-close designation failed.');
    $closed=bf013DbDispatch($router,'POST',"/ownerships/{$ownershipId}/close",$partner);
    bf013DbAssert($closed['status']===200 && $closed['payload']['data']['status']==='closed' && (int)$closed['payload']['data']['closed_by_user_id']===4 && $closed['payload']['data']['closed_at']!==null,'Ownership close persistence failed.');
    bf013DbAssert(! method_exists(OwnershipService::class,'reopen'), 'Ownership Service must not expose a reopen operation.');
    bf013DbAssert((int)$pdo->query("SELECT COUNT(*) FROM ownership_parties WHERE ownership_id={$ownershipId}")->fetchColumn()===3,'Closed Ownership Parties were not preserved.');
    bf013DbAssert((int)$pdo->query("SELECT COUNT(*) FROM authorized_acting_owner_designations WHERE ownership_id={$ownershipId}")->fetchColumn()===3,'Acting Owner history was not preserved.');
    foreach ([['POST',"/ownerships/{$ownershipId}/parties",['owner_id'=>$ownerBId]],['PUT',"/ownerships/{$ownershipId}/parties/{$party1Id}",['share_percentage'=>'10']],['DELETE',"/ownerships/{$ownershipId}/parties/{$party1Id}",[]],['POST',"/ownerships/{$ownershipId}/acting-owner",['ownership_party_id'=>$party1Id,'basis_source'=>'closed']],['PUT',"/ownerships/{$ownershipId}/acting-owner",['ownership_party_id'=>$party1Id,'basis_source'=>'closed']]] as [$method,$uri,$body]) { bf013DbAssert(bf013DbDispatch($router,$method,$uri,$partner,[],$body)['status']===422,"Closed Ownership mutation {$method} {$uri} was not rejected."); }
    bf013DbAssert(bf013DbDispatch($router,'GET',"/organization-properties/{$propertyId}/ownerships",$partner)['status']===200,'Ownership history endpoint failed.');
    bf013DbAssert(bf013DbDispatch($router,'GET','/ownerships/999999',$system)['status']===404,'Missing Ownership target must return 404.');

    $identity1=bf013DbDispatch($router,'POST','/global-physical-identities',$system); $identity2=bf013DbDispatch($router,'POST','/global-physical-identities',$system);
    bf013DbAssert($identity1['status']===201,'System Global Identity create failed.'); $identity1Id=bf013DbId($identity1); $identity2Id=bf013DbId($identity2);
    bf013DbAssert(bf013DbDispatch($router,'GET','/global-physical-identities',$limited)['status']===403,'System without capability accessed Global Identity.');
    bf013DbAssert(bf013DbDispatch($router,'GET','/global-physical-identities',$franchise)['status']===403 && bf013DbDispatch($router,'GET','/global-physical-identities',$partner)['status']===403,'Non-System actor accessed Global Identity.');
    $link=bf013DbDispatch($router,'POST',"/organization-properties/{$childPropertyId}/global-identity-link",$system,[],['global_physical_property_identity_id'=>$identity1Id]);
    bf013DbAssert($link['status']===201 && $link['payload']['data']['link_method']==='manual','Global Identity link failed.'); $link1Id=bf013DbId($link);
    bf013DbExpectConstraint($pdo, fn () => $pdo->exec("INSERT INTO global_physical_identity_links (ulid,organization_property_id,global_physical_property_identity_id,link_method,created_by_user_id,updated_by_user_id) VALUES ('00000000000000000000000009',{$childPropertyId},{$identity2Id},'manual',1,1)"), 'Active Global link generated guard was not enforced.');
    bf013DbAssert(bf013DbDispatch($router,'DELETE',"/organization-properties/{$childPropertyId}/global-identity-link",$system,[],[])['status']===422,'Blank unlink reason must return 422.');
    bf013DbAssert(bf013DbDispatch($router,'DELETE',"/organization-properties/{$childPropertyId}/global-identity-link",$system,[],['reason'=>'duplicate resolved'])['status']===200,'Global unlink failed.');
    bf013DbAssert($pdo->query("SELECT unlinked_at IS NOT NULL AND unlink_reason='duplicate resolved' FROM global_physical_identity_links WHERE id={$link1Id}")->fetchColumn()==1,'Exact Global link was not ended.');
    bf013DbAssert(bf013DbDispatch($router,'POST',"/organization-properties/{$childPropertyId}/global-identity-link",$system,[],['global_physical_property_identity_id'=>$identity1Id])['status']===201,'Global relink setup failed.');
    bf013DbAssert(bf013DbDispatch($router,'PUT',"/organization-properties/{$childPropertyId}/global-identity-link",$system,[],['global_physical_property_identity_id'=>$identity2Id,'reason'=>'correct identity'])['status']===200,'Global relink failed.');
    $linkHistory=bf013DbDispatch($router,'GET',"/organization-properties/{$childPropertyId}/global-identity-link/history",$system);
    bf013DbAssert($linkHistory['status']===200 && count($linkHistory['payload']['data'])===3,'Global link history sequence was not preserved.');
    bf013DbAssert(bf013DbDispatch($router,'GET',"/global-physical-identities/{$identity2Id}/representations",$system)['status']===200,'Identity representations failed.');
    bf013DbAssert(bf013DbDispatch($router,'GET','/global-physical-identities/999999/representations',$system)['status']===404,'Missing representations must return 404.');
    bf013DbExpectConstraint($pdo, fn () => $pdo->exec("INSERT INTO global_physical_identity_links (ulid,organization_property_id,global_physical_property_identity_id,link_method,created_by_user_id,updated_by_user_id) VALUES ('00000000000000000000000010',{$propertyId},{$identity1Id},'automatic',1,1)"), 'Global link method CHECK was not enforced.');
    bf013DbExpectConstraint($pdo, fn () => $pdo->exec("INSERT INTO property_owner_lifecycle_history (ulid,organization_id,action,from_status,to_status,created_by_user_id) VALUES ('00000000000000000000000011',3,'property_archive','active','archived',4)"), 'Lifecycle history exactly-one-resource CHECK was not enforced.');

    bf013DbAssert(bf013DbDispatch($router,'GET','/api/v1/health')['status']===200,'Health regression failed.');
    bf013DbAssert(bf013DbDispatch($router,'POST','/auth/login',null,[],['email'=>'bf013-system@example.test','password'=>'BF013-test-password'])['status']===200,'Authentication login regression failed.');
    bf013DbAssert(bf013DbDispatch($router,'GET','/auth/me',$system)['status']===200,'Authentication context regression failed.');
    foreach (['/organizations','/franchises','/partner-agencies','/users','/positions','/permissions'] as $uri) { bf013DbAssert(bf013DbDispatch($router,'GET',$uri,$system)['status']===200,"Legacy route {$uri} regression failed."); }

    echo "BF013 database-backed acceptance tests passed.\n";
} finally {
    $cleanup();
}
