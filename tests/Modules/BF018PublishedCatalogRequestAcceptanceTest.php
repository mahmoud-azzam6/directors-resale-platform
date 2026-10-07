<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Http\HeaderBag;
use App\Http\InputBag;
use App\Http\Request;
use App\Http\UploadedFile;
use App\Modules\Property\Services\PropertyPrimaryImageService;
use App\Providers\AppServiceProvider;
use App\Responses\Response;
use App\Routing\Router;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
$assertions = 0;
function interestAssert(bool $condition, string $message): void {
    global $assertions; ++$assertions;
    if (! $condition) { throw new RuntimeException($message); }
}
function interestRequest(Router $router, string $method, string $uri, ?string $token, array $data = []): array {
    parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
    $path = (string) parse_url($uri, PHP_URL_PATH);
    $request = new Request(new InputBag($query), new InputBag([]), new InputBag($data), new HeaderBag($token === null ? [] : ['Authorization' => 'Bearer ' . $token]), new InputBag([]), new InputBag([]), [], $method, $path);
    $response = $router->dispatch($method, $path, $request);
    $result = [];
    foreach (['statusCode' => 'status', 'payload' => 'payload'] as $property => $key) {
        $reflection = new ReflectionProperty(Response::class, $property);
        $reflection->setAccessible(true); $result[$key] = $reflection->getValue($response);
    }
    return $result;
}
function interestSnapshot(PDO $pdo): string {
    $state = [];
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        if ($table === 'listing_interest_requests') { continue; }
        $rows = array_map('serialize', $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC));
        sort($rows); $state[$table] = $rows;
    }
    return hash('sha256', serialize($state));
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
if (($argv[1] ?? null) === '--worker') {
    $workerName = $argv[2] ?? '';
    interestAssert(preg_match('/^directors_resale_platform_bf018_[0-9]+$/D', $workerName) === 1 && $workerName !== $config['connections']['mysql']['database'], 'Unsafe worker database.');
    $config['connections']['mysql']['database'] = $workerName;
    $_ENV['PROPERTY_IMAGE_STORAGE_PATH'] = $argv[3];
    $workerDatabase = new DatabaseManager($config);
    interestAssert($workerDatabase->connection()->query('SELECT DATABASE()')->fetchColumn() === $workerName, 'Worker identity mismatch.');
    $workerContainer = new Container(); (new AppServiceProvider($workerContainer, ['database' => $config]))->register();
    $workerContainer->instance(DatabaseConnectionInterface::class, $workerDatabase);
    $workerRouter = $workerContainer->make(Router::class); (require $root . '/routes/api.php')($workerRouter, $workerContainer, ['app' => ['version' => 'test']]);
    echo "READY\n"; flush();
    echo json_encode(interestRequest($workerRouter, 'POST', '/published-listings/' . (int) $argv[4] . '/requests', 'fixture-' . (int) $argv[5]));
    exit;
}
$real = $config['connections']['mysql'];
interestAssert($real['host'] === '127.0.0.1' && (int) $real['port'] === 3306 && $real['database'] === 'directors_resale_platform', 'Unexpected configured identity.');
$name = 'directors_resale_platform_bf018_' . getmypid();
interestAssert(preg_match('/^directors_resale_platform_bf018_[0-9]+$/D', $name) === 1 && $name !== $real['database'], 'Unsafe disposable database.');
$server = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', $real['username'], $real['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
interestAssert(str_contains($server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'MariaDB required.');
$exists = $server->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');
$exists->execute([$name]); interestAssert((int) $exists->fetchColumn() === 0, 'Existing database must not be reused.');
$storage = sys_get_temp_dir() . '/directors-bf018-images-' . getmypid();
interestAssert(! file_exists($storage), 'Existing files must not be reused.');
$oldStorage = $_ENV['PROPERTY_IMAGE_STORAGE_PATH'] ?? null;
$_ENV['PROPERTY_IMAGE_STORAGE_PATH'] = $storage;
$source = $storage . '/source.png';
$created = false;
try {
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created = true;
    $config['connections']['mysql']['database'] = $name;
    $database = new DatabaseManager($config); $pdo = $database->connection();
    interestAssert($pdo->query('SELECT DATABASE()')->fetchColumn() === $name, 'Real database setup prohibited.');
    $files = glob($root . '/database/migrations/*.sql'); sort($files, SORT_STRING);
    foreach ($files as $file) { $pdo->exec(file_get_contents($file)); }
    interestAssert((int) $pdo->query("SELECT COUNT(*) FROM permissions ")->fetchColumn() === 37, 'Exactly 37 canonical permissions are required.');
    interestAssert($pdo->query("SELECT code FROM permissions WHERE code LIKE 'listings.%' ORDER BY code")->fetchAll(PDO::FETCH_COLUMN) === ['listings.manage', 'listings.view'], 'Exact Listing capabilities.');
    $ddl = $pdo->query('SHOW CREATE TABLE listings')->fetch(PDO::FETCH_NUM)[1];
    foreach (['uq_listings_active_property','fk_listing_property_organization','fk_listing_created_by','fk_listing_updated_by','chk_listing_revision','chk_listing_lifecycle'] as $constraint) { interestAssert(str_contains($ddl, $constraint), 'Missing constraint: ' . $constraint); }
    $columns = $pdo->query('SHOW COLUMNS FROM listings')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['property_code','address_text','initial_asking_price','currency_code','storage_key','ownership_id','property_label'] as $field) { interestAssert(! in_array($field, $columns, true), 'Property data must not be copied: ' . $field); }
    $pdo->exec("INSERT INTO organizations (id,parent_organization_id,name,code,organization_type,status) VALUES (1,NULL,'System','SYS','system','active'),(2,1,'Franchise','F','franchise','active'),(3,2,'Child','C','partner_agency','active'),(4,1,'Peer','P','franchise','active')");
    $pdo->exec("INSERT INTO positions (id,organization_id,name,code,status) VALUES (1,1,'System','S','active'),(2,2,'Franchise','F','active'),(3,3,'Child','C','active'),(4,4,'Peer','P','active'),(5,2,'Property only','PROP','active'),(6,2,'Listing read','READ','active'),(7,2,'Listing manage','WRITE','active')");
    $user = $pdo->prepare('INSERT INTO users (id,organization_id,position_id,full_name,email,status) VALUES (?,?,?,?,?,?)');
    $token = $pdo->prepare('INSERT INTO auth_tokens (user_id,token_hash,expires_at) VALUES (?,?,?)');
    foreach (range(1,7) as $id) {
        $user->execute([$id, $id <= 4 ? $id : 2, $id, 'Fixture', "listing$id@example.test", 'active']);
        $token->execute([$id, hash('sha256', 'fixture-' . $id), '2999-01-01']);
    }
    $pdo->exec("INSERT INTO position_permissions (position_id,permission_id) SELECT p.id,c.id FROM positions p CROSS JOIN permissions c WHERE (p.id<=4 AND c.code IN ('listings.view','listings.manage')) OR (p.id=5 AND c.code IN ('properties.view','properties.manage')) OR (p.id=6 AND c.code='listings.view') OR (p.id=7 AND c.code='listings.manage')");
    $pdo->exec("INSERT INTO position_permissions (position_id,permission_id) SELECT 2,id FROM permissions WHERE code='properties.view'");
    $pdo->exec("INSERT INTO organization_properties (id,ulid,organization_id,property_label,status,created_by_user_id,updated_by_user_id) VALUES (1,'01J00000000000000000000001',2,'Duplicate name','active',2,2),(2,'01J00000000000000000000002',3,'Duplicate name','active',3,3),(3,'01J00000000000000000000003',4,'Peer','active',4,4),(4,'01J00000000000000000000004',1,'System','active',1,1)");
    $pdo->exec("INSERT INTO geographic_locations (id,ulid,code,name_ar,name_en,status,provenance,type) VALUES (1,'01J00000000000000000000005','COUNTRY','Egypt','Egypt','active','SYSTEM_ADMIN','COUNTRY')");
    $pdo->exec("INSERT INTO organization_property_profiles (organization_property_id,geographic_location_id,address_text,initial_asking_price,currency_code,created_by_user_id,updated_by_user_id) VALUES (1,1,'Address',100,'EGP',2,2),(2,1,'Child Address',200,'USD',3,3)");
    $pdo->exec("INSERT INTO owners (id,ulid,organization_id,party_type,display_name,status,created_by_user_id,updated_by_user_id) VALUES (1,'01J00000000000000000000006',2,'individual','Private Owner','active',2,2),(2,'01J00000000000000000000007',3,'individual','Child private Owner','active',3,3)");
    $pdo->exec("INSERT INTO ownerships (id,ulid,organization_property_id,organization_id,status,created_by_user_id,updated_by_user_id) VALUES (1,'01J00000000000000000000008',1,2,'current',2,2),(2,'01J00000000000000000000009',2,3,'current',3,3)");
    $pdo->exec("INSERT INTO ownership_parties (ulid,ownership_id,owner_id,organization_id,share_percentage,created_by_user_id,updated_by_user_id) VALUES ('01J00000000000000000000010',1,1,2,100,2,2),('01J00000000000000000000011',2,2,3,100,3,3)");
    $container = new Container(); (new AppServiceProvider($container, ['database' => $config]))->register();
    $container->instance(DatabaseConnectionInterface::class, $database);
    $router = $container->make(Router::class); (require $root . '/routes/api.php')($router, $container, ['app' => ['version' => 'test']]);
    $routeProperty = new ReflectionProperty(Router::class, 'routes'); $routeProperty->setAccessible(true);
    $listingRoutes = [];
    foreach ($routeProperty->getValue($router) as $method => $handlers) {
        foreach (array_keys($handlers) as $path) { if (str_starts_with($path, '/listings')) { $listingRoutes[] = $method . ' ' . $path; } }
    }
    sort($listingRoutes);
    interestAssert($listingRoutes === ['GET /listings','GET /listings/{id}','GET /listings/{id}/primary-image/content','POST /listings','POST /listings/{id}/archive','POST /listings/{id}/publish'], 'Exactly the six approved Listing routes.');
    mkdir($storage, 0700); $canvas = imagecreatetruecolor(600,600); imagepng($canvas,$source); imagedestroy($canvas);
    $images = $container->make(PropertyPrimaryImageService::class);
    foreach ([1 => 2, 2 => 3] as $property => $organization) { $images->replace($property,$organization,$organization,new UploadedFile('source.png','image/png',$source,0,filesize($source))); }
    $call = fn (string $method, string $path, ?int $actor = 2, array $data = []) => interestRequest($router,$method,$path,$actor === null ? null : 'fixture-' . $actor,$data);
    foreach ([8 => 2, 9 => 3, 10 => 4, 11 => 2, 12 => 2, 13 => 1, 14 => 2] as $actor => $organization) {
        $pdo->exec("INSERT INTO positions (id,organization_id,name,code,status) VALUES ($actor,$organization,'Catalog fixture','CAT$actor','active')");
        $user->execute([$actor, $organization, $actor, 'Fixture', "catalog$actor@example.test", 'active']);
        $token->execute([$actor, hash('sha256', 'fixture-' . $actor), '2999-01-01']);
    }
    $pdo->exec("INSERT INTO position_permissions (position_id,permission_id) SELECT p.id,c.id FROM positions p CROSS JOIN permissions c WHERE (p.id IN (8,9,10,13) AND c.code IN ('published_listings.view','requests.create','requests.view')) OR (p.id=11 AND c.code='published_listings.view') OR (p.id=12 AND c.code='requests.create') OR (p.id=14 AND c.code='requests.view')");
    $draft = $call('POST', '/listings', 2, ['organization_property_id' => 1]);
    $id = $draft['payload']['data']['id'];
    $child = $call('POST', '/listings', 3, ['organization_property_id' => 2])['payload']['data']['id'];
    interestAssert($call('GET', '/published-listings', 8)['payload']['data'] === [], 'Drafts excluded.');
    foreach (['GET', 'POST'] as $method) { interestAssert($call($method, '/published-listings/' . $id . ($method === 'POST' ? '/requests' : ''), 8)['status'] >= 400, 'Draft detail/request denied.'); }
    interestAssert($call('POST', "/listings/$id/publish", 2, ['revision' => 1])['status'] === 200, 'Fixture publish.');
    interestAssert($call('POST', "/listings/$child/publish", 3, ['revision' => 1])['status'] === 200, 'Child fixture publish.');
    $catalog = $call('GET', '/published-listings', 8);
    interestAssert($catalog['status'] === 200 && count($catalog['payload']['data']) === 2, 'Own and child catalog.');
    interestAssert(count($call('GET', '/published-listings', 9)['payload']['data']) === 1, 'Partner own only.');
    interestAssert($call('GET', '/published-listings', 10)['payload']['data'] === [], 'Peer catalog isolation.');
    foreach ([null => 401, 2 => 403, 12 => 403] as $actor => $status) { interestAssert($call('GET', '/published-listings', $actor === '' ? null : (int) $actor)['status'] === $status, 'Catalog separate capability/anonymous.'); }
    interestAssert($call('GET', "/published-listings/$id", 10)['status'] === 403, 'Peer detail denial.');
    interestAssert($call('POST', "/published-listings/$id/requests", 10)['status'] === 403, 'Peer write denial.');
    interestAssert($call('GET', '/published-listings?organization_id=1', 8)['status'] === 403, 'System scope denied.');
    interestAssert($call('GET', '/published-listings?organization_id=4', 8)['status'] === 403, 'Peer filter denied.');
    interestAssert($call('GET', '/published-listings/999999', 8)['status'] === 404, 'Missing link denied.');
    interestAssert($call('GET', '/published-listings/not-an-id', 8)['status'] === 404, 'Invalid link denied.');
    interestAssert($call('GET', "/published-listings/$id/primary-image/content", 8)['status'] === 200, 'Protected catalog image.');
    interestAssert($call('GET', "/listings/$id", 8)['status'] === 403, 'Customer never gains Draft/admin read.');
    interestAssert($call('POST', "/listings/$id/archive", 8, ['revision' => 2])['status'] === 403, 'Customer never gains administration.');
    foreach ([null => 401, 2 => 403, 11 => 403, 12 => 403] as $actor => $status) { interestAssert($call('POST', "/published-listings/$id/requests", $actor === '' ? null : (int) $actor)['status'] === $status, 'Both catalog and submit capabilities required.'); }
    interestAssert($call('POST', "/published-listings/$id/requests", 8, ['requester_user_id' => 9])['status'] === 422, 'Actor tampering rejected.');
    $snapshot = interestSnapshot($pdo);
    $result = $call('POST', "/published-listings/$id/requests", 8);
    interestAssert($result['status'] === 201, 'Valid interest stored.');
    $receipt = $result['payload']['data'];
    $stored = $pdo->query('SELECT * FROM listing_interest_requests')->fetch(PDO::FETCH_ASSOC);
    interestAssert((int) $stored['listing_id'] === $id && (int) $stored['organization_property_id'] === 1 && (int) $stored['listing_organization_id'] === 2 && (int) $stored['requester_user_id'] === 8 && (int) $stored['requester_organization_id'] === 2, 'Exact server-derived references.');
    interestAssert($call('POST', "/published-listings/$id/requests", 8)['status'] === 409, 'Duplicate denied.');
    interestAssert(count($call('GET', '/my-listing-requests', 8)['payload']['data']) === 1, 'Own receipt list.');
    interestAssert($call('GET', '/my-listing-requests/' . $receipt['id'], 8)['status'] === 200, 'Own receipt detail.');
    interestAssert($call('GET', '/my-listing-requests/' . $receipt['id'], 9)['status'] === 404, 'Other requester denied.');
    interestAssert($call('GET', '/my-listing-requests/' . $receipt['id'], 13)['status'] === 404, 'System capability does not bypass receipt ownership.');
    interestAssert($call('GET', '/my-listing-requests', 11)['status'] === 403, 'Receipt capability independent.');
    interestAssert($call('GET', '/my-listing-requests', 14)['payload']['data'] === [], 'Receipt-only user sees only own.');
    interestAssert($call('POST', "/published-listings/$child/requests", 8)['status'] === 201, 'Franchise authorized child interest.');
    interestAssert($call('POST', "/published-listings/$child/requests", 9)['status'] === 201, 'Different users can request same Listing.');
    interestAssert(interestSnapshot($pdo) === $snapshot, 'Requests never mutate existing domains.');
    foreach ([
        ["UPDATE organization_properties SET ulid='' WHERE id=1", "UPDATE organization_properties SET ulid='01J00000000000000000000001' WHERE id=1"],
        ["UPDATE organization_property_profiles SET address_text=NULL WHERE organization_property_id=1", "UPDATE organization_property_profiles SET address_text='Address' WHERE organization_property_id=1"],
        ["UPDATE organization_property_profiles SET initial_asking_price=NULL,currency_code=NULL WHERE organization_property_id=1", "UPDATE organization_property_profiles SET initial_asking_price=100,currency_code='EGP' WHERE organization_property_id=1"],
        ["UPDATE organization_property_profiles SET initial_asking_price=0 WHERE organization_property_id=1", "UPDATE organization_property_profiles SET initial_asking_price=100 WHERE organization_property_id=1"],
        ["UPDATE organization_property_profiles SET currency_code='XYZ' WHERE organization_property_id=1", "UPDATE organization_property_profiles SET currency_code='EGP' WHERE organization_property_id=1"],
        ["UPDATE ownerships SET status='closed',closed_at=CURRENT_TIMESTAMP,closed_by_user_id=2 WHERE id=1", "UPDATE ownerships SET status='current',closed_at=NULL,closed_by_user_id=NULL WHERE id=1"],
        ["UPDATE owners SET status='inactive',deactivated_at=CURRENT_TIMESTAMP,deactivated_by_user_id=2 WHERE id=1", "UPDATE owners SET status='active',deactivated_at=NULL,deactivated_by_user_id=NULL WHERE id=1"],
        ["UPDATE geographic_locations SET status='inactive' WHERE id=1", "UPDATE geographic_locations SET status='active' WHERE id=1"],
        ["UPDATE organizations SET status='inactive' WHERE id=2", "UPDATE organizations SET status='active' WHERE id=2"],
        ["UPDATE organization_properties SET status='archived',archived_at=CURRENT_TIMESTAMP,archived_by_user_id=2 WHERE id=1", "UPDATE organization_properties SET status='active',archived_at=NULL,archived_by_user_id=NULL WHERE id=1"],
    ] as [$invalidate, $restore]) {
        // Invalid legacy fixtures only; real database is never selected. Restore checks before schema assertions.
        $pdo->exec('SET SESSION check_constraint_checks=OFF');
        $pdo->exec($invalidate);
        interestAssert($call('GET', "/published-listings/$id", 8)['status'] === 404, 'Invalid current prerequisite detail unavailable.');
        interestAssert($call('POST', "/published-listings/$id/requests", 8)['status'] === 422, 'Invalid current prerequisite Request denied even after prior interest.');
        interestAssert(! in_array($id, array_column($call('GET', '/published-listings', 8)['payload']['data'], 'id'), true), 'Invalid current prerequisite omitted.');
        $pdo->exec($restore);
        $pdo->exec('SET SESSION check_constraint_checks=ON');
    }
    $imagePath = $storage . '/' . $pdo->query('SELECT storage_key FROM property_primary_images WHERE organization_property_id=1')->fetchColumn() . '.webp';
    $imageBytes = file_get_contents($imagePath); unlink($imagePath);
    interestAssert($call('GET', "/published-listings/$id/primary-image/content", 8)['status'] === 404, 'Missing image unavailable.');
    interestAssert($call('POST', "/published-listings/$id/requests", 8)['status'] === 422, 'Unreadable image blocks interest.');
    file_put_contents($imagePath, $imageBytes);
    $pdo->exec("INSERT INTO position_permissions (position_id,permission_id) SELECT 11,id FROM permissions WHERE code='requests.create'");
    // Queue two real PHP workers behind the same Property lock. Neither may use the real database.
    $pdo->beginTransaction(); $pdo->query('SELECT id FROM organization_properties WHERE id=1 FOR UPDATE')->fetch();
    $workers = [];
    foreach ([1, 2] as $worker) {
        $process = proc_open([PHP_BINARY, __FILE__, '--worker', $name, $storage, (string) $id, '11'], [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes, $root);
        interestAssert(is_resource($process), 'Worker started.'); fclose($pipes[0]);
        $workers[] = [$process, $pipes];
        stream_set_timeout($pipes[1], 20);
        interestAssert(trim((string) fgets($pipes[1])) === 'READY', 'Worker ready.');
    }
    $pdo->commit(); $statuses = [];
    foreach ($workers as [$process, $pipes]) {
        $output = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
        interestAssert(proc_close($process) === 0 && $stderr === '', 'Worker execution.');
        $statuses[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR)['status'];
    }
    $workers = []; sort($statuses); interestAssert($statuses === [201,409], 'Concurrent duplicate gives one success and one already-requested.');
    interestAssert((int) $pdo->query("SELECT COUNT(*) FROM listing_interest_requests WHERE requester_user_id=11 AND listing_id=$id")->fetchColumn() === 1, 'Exactly one concurrent row.');
    $pdo->exec("INSERT INTO position_permissions (position_id,permission_id) SELECT 12,id FROM permissions WHERE code='published_listings.view'");
    $pdo->beginTransaction(); $pdo->query('SELECT id FROM organization_properties WHERE id=1 FOR UPDATE')->fetch();
    $process = proc_open([PHP_BINARY, __FILE__, '--worker', $name, $storage, (string) $id, '12'], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, $root);
    interestAssert(is_resource($process), 'Stale worker started.'); fclose($pipes[0]); $workers = [[$process,$pipes]];
    stream_set_timeout($pipes[1],20); interestAssert(trim((string) fgets($pipes[1])) === 'READY', 'Stale worker ready.');
    $pdo->exec('UPDATE organization_property_profiles SET initial_asking_price=NULL,currency_code=NULL WHERE organization_property_id=1'); $pdo->commit();
    $output = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    interestAssert(proc_close($process) === 0 && $stderr === '', 'Stale worker execution.'); $workers = [];
    interestAssert(json_decode($output,true,512,JSON_THROW_ON_ERROR)['status'] === 422, 'Waiter sees committed Property invalidation, not an old read snapshot.');
    interestAssert((int) $pdo->query('SELECT COUNT(*) FROM listing_interest_requests WHERE requester_user_id=12')->fetchColumn() === 0, 'Stale availability inserts nothing.');
    $pdo->exec("UPDATE organization_property_profiles SET initial_asking_price=100,currency_code='EGP' WHERE organization_property_id=1");
    $requestDdl = $pdo->query('SHOW CREATE TABLE listing_interest_requests')->fetch(PDO::FETCH_NUM)[1];
    foreach (['uq_interest_request_ulid','uq_interest_request_user_listing','idx_interest_request_user','fk_interest_request_listing','fk_interest_request_property','fk_interest_request_user','chk_interest_request_status'] as $constraint) { interestAssert(str_contains($requestDdl,$constraint), 'Request schema constraint.'); }
    foreach (["UPDATE listing_interest_requests SET organization_property_id=2 WHERE id=" . $receipt['id'], "UPDATE listing_interest_requests SET requester_organization_id=3 WHERE id=" . $receipt['id'], "UPDATE listing_interest_requests SET status='deal' WHERE id=" . $receipt['id']] as $sql) {
        try { $pdo->exec($sql); throw new RuntimeException('Constraint bypass.'); } catch (PDOException) { ++$assertions; }
    }
    interestAssert($call('POST', "/listings/$id/archive", 2, ['revision' => 2])['status'] === 200, 'BF017 archive still works with Requests.');
    interestAssert($call('GET', "/published-listings/$id", 8)['status'] === 404, 'Archived detail unavailable.');
    interestAssert($call('POST', "/published-listings/$id/requests", 8)['status'] === 422, 'Archived interest denied.');
    interestAssert($call('GET', '/my-listing-requests/' . $receipt['id'], 8)['status'] === 200, 'Own receipt preserved after archive.');
    interestAssert($pdo->query("SELECT code FROM permissions WHERE code IN ('published_listings.view','requests.create','requests.view') ORDER BY code")->fetchAll(PDO::FETCH_COLUMN) === ['published_listings.view','requests.create','requests.view'], 'Exact three approved canonical capabilities.');
    echo "BF018 catalog/interest acceptance: PASS ($assertions assertions)\n";
} finally {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    foreach ($workers ?? [] as [$process, $pipes]) { if (is_resource($process)) { proc_terminate($process); foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } } proc_close($process); } }
    if ($created) {
        interestAssert($name !== $real['database'] && preg_match('/^directors_resale_platform_bf018_[0-9]+$/D', $name) === 1, 'Unsafe cleanup target.');
        $server->exec("DROP DATABASE `$name`"); $exists->execute([$name]); interestAssert((int) $exists->fetchColumn() === 0, 'Database residue.');
    }
    if (is_dir($storage)) { foreach (glob($storage . '/*') ?: [] as $file) { unlink($file); } rmdir($storage); }
    interestAssert(! file_exists($storage), 'File residue.');
    if ($oldStorage === null) { unset($_ENV['PROPERTY_IMAGE_STORAGE_PATH']); } else { $_ENV['PROPERTY_IMAGE_STORAGE_PATH'] = $oldStorage; }
    echo "BF018 disposable database/file cleanup: PASS\n";
}
