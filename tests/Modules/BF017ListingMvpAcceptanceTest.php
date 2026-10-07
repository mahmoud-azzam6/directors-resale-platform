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
function listingAssert(bool $condition, string $message): void {
    global $assertions; ++$assertions;
    if (! $condition) { throw new RuntimeException($message); }
}
function listingRequest(Router $router, string $method, string $uri, ?string $token, array $data = []): array {
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
function listingSnapshot(PDO $pdo): string {
    $state = [];
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        if ($table === 'listings') { continue; }
        $rows = array_map('serialize', $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC));
        sort($rows); $state[$table] = $rows;
    }
    return hash('sha256', serialize($state));
}

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$real = $config['connections']['mysql'];
listingAssert($real['host'] === '127.0.0.1' && (int) $real['port'] === 3306 && $real['database'] === 'directors_resale_platform', 'Unexpected configured identity.');
$name = 'directors_resale_platform_bf017_' . getmypid();
listingAssert(preg_match('/^directors_resale_platform_bf017_[0-9]+$/D', $name) === 1 && $name !== $real['database'], 'Unsafe disposable database.');
$server = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', $real['username'], $real['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
listingAssert(str_contains($server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'MariaDB required.');
$exists = $server->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');
$exists->execute([$name]); listingAssert((int) $exists->fetchColumn() === 0, 'Existing database must not be reused.');
$storage = sys_get_temp_dir() . '/directors-bf017-images-' . getmypid();
listingAssert(! file_exists($storage), 'Existing files must not be reused.');
$oldStorage = $_ENV['PROPERTY_IMAGE_STORAGE_PATH'] ?? null;
$_ENV['PROPERTY_IMAGE_STORAGE_PATH'] = $storage;
$source = $storage . '/source.png';
$created = false;
try {
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created = true;
    $config['connections']['mysql']['database'] = $name;
    $database = new DatabaseManager($config); $pdo = $database->connection();
    listingAssert($pdo->query('SELECT DATABASE()')->fetchColumn() === $name, 'Real database setup prohibited.');
    $files = glob($root . '/database/migrations/*.sql'); sort($files, SORT_STRING);
    foreach ($files as $file) { $pdo->exec(file_get_contents($file)); }
    listingAssert((int) $pdo->query("SELECT COUNT(*) FROM permissions WHERE code NOT IN ('published_listings.view','requests.create','requests.view')")->fetchColumn() === 34, 'Exactly two BF017 Listing codes must be added before BF018 capabilities.');
    listingAssert($pdo->query("SELECT code FROM permissions WHERE code LIKE 'listings.%' ORDER BY code")->fetchAll(PDO::FETCH_COLUMN) === ['listings.manage', 'listings.view'], 'Exact Listing capabilities.');
    $ddl = $pdo->query('SHOW CREATE TABLE listings')->fetch(PDO::FETCH_NUM)[1];
    foreach (['uq_listings_active_property','fk_listing_property_organization','fk_listing_created_by','fk_listing_updated_by','chk_listing_revision','chk_listing_lifecycle'] as $constraint) { listingAssert(str_contains($ddl, $constraint), 'Missing constraint: ' . $constraint); }
    $columns = $pdo->query('SHOW COLUMNS FROM listings')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['property_code','address_text','initial_asking_price','currency_code','storage_key','ownership_id','property_label'] as $field) { listingAssert(! in_array($field, $columns, true), 'Property data must not be copied: ' . $field); }
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
    listingAssert($listingRoutes === ['GET /listings','GET /listings/{id}','GET /listings/{id}/primary-image/content','POST /listings','POST /listings/{id}/archive','POST /listings/{id}/publish'], 'Exactly the six approved Listing routes.');
    mkdir($storage, 0700); $canvas = imagecreatetruecolor(600,600); imagepng($canvas,$source); imagedestroy($canvas);
    $images = $container->make(PropertyPrimaryImageService::class);
    foreach ([1 => 2, 2 => 3] as $property => $organization) { $images->replace($property,$organization,$organization,new UploadedFile('source.png','image/png',$source,0,filesize($source))); }
    $call = fn (string $method, string $path, ?int $actor = 2, array $data = []) => listingRequest($router,$method,$path,$actor === null ? null : 'fixture-' . $actor,$data);
    $snapshot = listingSnapshot($pdo);
    foreach ([null => 401, 5 => 403] as $actor => $status) {
        $actor = $actor === '' ? null : (int) $actor;
        listingAssert($call('GET','/listings',$actor)['status'] === $status, 'Listing read capability/anonymous denial.');
    }
    listingAssert($call('POST','/listings',6,['organization_property_id'=>1])['status'] === 403, 'View must not imply manage.');
    listingAssert($call('POST','/listings',5,['organization_property_id'=>1])['status'] === 403, 'Property manage must not authorize Listing creation.');
    listingAssert($call('POST','/listings',null,['organization_property_id'=>1])['status'] === 401, 'Anonymous draft creation denied.');
    listingAssert($call('GET','/listings',7)['status'] === 403, 'Manage must not imply view.');
    foreach ([3,4] as $property) { listingAssert($call('POST','/listings',2,['organization_property_id'=>$property])['status'] === 403, 'Peer/System Property denied.'); }
    listingAssert($call('POST','/listings',3,['organization_property_id'=>1])['status'] === 403, 'Partner cannot create parent Listing.');
    listingAssert($call('POST','/listings',2,['organization_property_id'=>999])['status'] === 404, 'Invalid Property denied.');
    listingAssert($call('POST','/listings',2,['organization_property_id'=>1,'organization_id'=>4])['status'] === 422, 'Client ownership override rejected.');
    $draft = $call('POST','/listings',2,['organization_property_id'=>1]); listingAssert($draft['status'] === 201, 'Franchise draft succeeds.');
    $id = $draft['payload']['data']['id']; listingAssert($draft['payload']['data']['status'] === 'draft' && $draft['payload']['data']['organization_id'] === 2, 'Derived owner and draft lifecycle.');
    listingAssert($call('GET','/organization-properties/1/profile',2)['status'] === 200, 'Parent fixture has its own Property read capability.');
    foreach (['publish','archive'] as $operation) { listingAssert($call('POST',"/listings/$id/$operation",null,['revision'=>1])['status'] === 401, 'Anonymous lifecycle mutation denied.'); }
    listingAssert($call('GET',"/listings/$id/primary-image/content",null)['status'] === 401, 'Anonymous image denied.');
    $child = $call('POST','/listings',3,['organization_property_id'=>2]); listingAssert($child['status'] === 201, 'Partner draft succeeds.');
    $childId = $child['payload']['data']['id'];
    listingAssert($call('GET',"/listings/$childId",2)['status'] === 200, 'Parent Listing projection authorized.');
    listingAssert($call('GET','/organization-properties/2/profile',2)['status'] === 403, 'Existing private Property routes unchanged.');
    listingAssert($call('GET','/listings?organization_id=4',2)['status'] === 403, 'Peer list filter denied.');
    listingAssert(count($call('GET','/listings',2)['payload']['data']) === 2, 'Hierarchy list scoped.');
    listingAssert(count($call('GET','/listings',3)['payload']['data']) === 1, 'Partner list own-only.');
    listingAssert(count($call('GET','/listings',1)['payload']['data']) === 2, 'System-authorized support unchanged.');
    listingAssert($call('POST','/listings',2,['organization_property_id'=>1])['status'] === 422, 'Duplicate active denied.');
    foreach ([['revision'=>0], ['revision'=>'1e0'], ['revision'=>1,'status'=>'published']] as $input) { listingAssert($call('POST',"/listings/$id/publish",2,$input)['status'] === 422, 'Invalid transition input denied.'); }
    foreach ([4,3,5] as $actor) {
        foreach (['GET' => '', 'POST' => '/publish'] as $method => $suffix) { listingAssert($call($method,"/listings/$id$suffix",$actor,['revision'=>1])['status'] === 403, 'Unauthorized read/mutation denied.'); }
        listingAssert($call('GET',"/listings/$id/primary-image/content",$actor)['status'] === 403, 'Unauthorized image denied.');
    }
    listingAssert(listingSnapshot($pdo) === $snapshot, 'Draft/denied operations must not modify Property aggregates.');
    $cases = [
        ['property_code',"UPDATE organization_properties SET ulid='' WHERE id=1","UPDATE organization_properties SET ulid='01J00000000000000000000001' WHERE id=1"],
        ['address','UPDATE organization_property_profiles SET address_text=NULL WHERE organization_property_id=1',"UPDATE organization_property_profiles SET address_text='Address' WHERE organization_property_id=1"],
        ['address','UPDATE organization_property_profiles SET geographic_location_id=NULL WHERE organization_property_id=1','UPDATE organization_property_profiles SET geographic_location_id=1 WHERE organization_property_id=1'],
        ['address',"UPDATE geographic_locations SET status='inactive' WHERE id=1","UPDATE geographic_locations SET status='active' WHERE id=1"],
        ['address','UPDATE geographic_locations SET parent_id=1 WHERE id=1','UPDATE geographic_locations SET parent_id=NULL WHERE id=1'],
        ['organization_property_id',"UPDATE organization_properties SET status='archived',archived_at=NOW(),archived_by_user_id=2 WHERE id=1","UPDATE organization_properties SET status='active',archived_at=NULL,archived_by_user_id=NULL WHERE id=1"],
        ['organization_property_id',"UPDATE organizations SET status='inactive' WHERE id=2","UPDATE organizations SET status='active' WHERE id=2"],
        ['price','UPDATE organization_property_profiles SET initial_asking_price=NULL WHERE organization_property_id=1','UPDATE organization_property_profiles SET initial_asking_price=100 WHERE organization_property_id=1'],
        ['currency','UPDATE organization_property_profiles SET currency_code=NULL WHERE organization_property_id=1',"UPDATE organization_property_profiles SET currency_code='EGP' WHERE organization_property_id=1"],
        ['currency',"UPDATE organization_property_profiles SET currency_code='XYZ' WHERE organization_property_id=1","UPDATE organization_property_profiles SET currency_code='EGP' WHERE organization_property_id=1"],
        ['price','UPDATE organization_property_profiles SET initial_asking_price=0 WHERE organization_property_id=1','UPDATE organization_property_profiles SET initial_asking_price=100 WHERE organization_property_id=1'],
        ['ownership',"UPDATE ownerships SET status='closed',closed_at=NOW(),closed_by_user_id=2 WHERE id=1","UPDATE ownerships SET status='current',closed_at=NULL,closed_by_user_id=NULL WHERE id=1"],
        ['ownership',"UPDATE owners SET status='inactive',deactivated_at=NOW(),deactivated_by_user_id=2 WHERE id=1","UPDATE owners SET status='active',deactivated_at=NULL,deactivated_by_user_id=NULL WHERE id=1"],
    ];
    foreach ($cases as [$field,$break,$restore]) {
        // Corrupt stored prerequisites only inside this disposable database, then restore.
        $pdo->exec('SET SESSION check_constraint_checks=OFF'); $pdo->exec($break);
        $state = listingSnapshot($pdo); $result = $call('POST',"/listings/$id/publish",2,['revision'=>1]);
        listingAssert($result['status'] === 422 && isset($result['payload']['error']['fields'][$field]), 'Missing/invalid publication prerequisite: ' . $field);
        listingAssert(listingSnapshot($pdo) === $state && $pdo->query("SELECT revision FROM listings WHERE id=$id")->fetchColumn() == 1, 'Failed publish atomic.');
        $pdo->exec($restore); $pdo->exec('SET SESSION check_constraint_checks=ON');
    }
    $imageKey = $pdo->query('SELECT storage_key FROM property_primary_images WHERE organization_property_id=1')->fetchColumn();
    $imagePath = $storage . '/' . $imageKey . '.webp'; $bytes = file_get_contents($imagePath);
    unlink($imagePath);
    listingAssert($call('POST',"/listings/$id/publish",2,['revision'=>1])['status'] === 422, 'Missing image file blocks publication.');
    file_put_contents($imagePath,$bytes);
    $images->remove(1,2);
    listingAssert($call('POST',"/listings/$id/publish",2,['revision'=>1])['status'] === 422, 'Missing image metadata blocks publication.');
    $images->replace(1,2,2,new UploadedFile('source.png','image/png',$source,0,filesize($source)));
    $state = listingSnapshot($pdo);
    $published = $call('POST',"/listings/$id/publish",2,['revision'=>1]);
    listingAssert($published['status'] === 200 && $published['payload']['data']['status'] === 'published' && $published['payload']['data']['revision'] === 2, 'Direct publication succeeds.');
    listingAssert($call('POST',"/listings/$childId/publish",3,['revision'=>1])['status'] === 200, 'Partner publication succeeds.');
    listingAssert($call('POST',"/listings/$childId/archive",2,['revision'=>2])['status'] === 200, 'Authorized parent child Listing management succeeds.');
    listingAssert($call('GET',"/listings/$id/primary-image/content",6)['status'] === 200, 'Listing-only image read succeeds.');
    listingAssert($call('POST',"/listings/$id/archive",2,['revision'=>1])['status'] === 409, 'Stale revision denied.');
    listingAssert($call('POST',"/listings/$id/archive",2,['revision'=>2])['status'] === 200, 'Published archive succeeds.');
    listingAssert($call('POST',"/listings/$id/publish",2,['revision'=>3])['status'] === 422, 'Archived Listing cannot republish.');
    listingAssert(listingSnapshot($pdo) === $state, 'Publish/archive must not change any other table.');
    listingAssert($call('POST','/listings',7,['organization_property_id'=>1])['status'] === 201, 'Manage-only draft after archive succeeds.');
    $pdo->exec("UPDATE organization_property_profiles SET initial_asking_price=125 WHERE organization_property_id=1");
    $read = $call('GET',"/listings/$id",6)['payload']['data'];
    listingAssert((float) $read['property']['initial_asking_price'] === 125.0 && $read['property']['property_code'] === 'PROP-01J00000000000000000000001', 'Projection reflects Property source of truth.');
    listingAssert(! str_contains(json_encode($read), 'Private Owner') && ! str_contains(json_encode($read), 'storage_key'), 'No Owner contact or storage key leaks.');
    foreach (["UPDATE listings SET revision=0 WHERE id=$id", "UPDATE listings SET status='published',published_at=NULL,archived_at=NULL WHERE id=$id", "INSERT INTO listings (ulid,organization_property_id,organization_id,created_by_user_id,updated_by_user_id) VALUES ('01J00000000000000000000020',1,2,2,2)", "INSERT INTO listings (ulid,organization_property_id,organization_id,created_by_user_id,updated_by_user_id) VALUES ('01J00000000000000000000021',3,2,2,2)"] as $sql) {
        try { $pdo->exec($sql); throw new RuntimeException('Database constraint not enforced.'); } catch (PDOException) { ++$assertions; }
    }
    echo "BF017 Listing acceptance: PASS ($assertions assertions)\n";
} finally {
    if ($created) {
        listingAssert($name !== $real['database'] && preg_match('/^directors_resale_platform_bf017_[0-9]+$/D',$name) === 1, 'Unsafe cleanup target.');
        if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
        $server->exec("DROP DATABASE `$name`");
        $exists->execute([$name]); listingAssert((int) $exists->fetchColumn() === 0, 'Disposable database residue.');
    }
    if (is_dir($storage)) { foreach (glob($storage . '/*') ?: [] as $file) { unlink($file); } rmdir($storage); }
    listingAssert(! file_exists($storage), 'Disposable file residue.');
    if ($oldStorage === null) { unset($_ENV['PROPERTY_IMAGE_STORAGE_PATH']); } else { $_ENV['PROPERTY_IMAGE_STORAGE_PATH'] = $oldStorage; }
    echo "BF017 disposable database/file cleanup: PASS\n";
}
