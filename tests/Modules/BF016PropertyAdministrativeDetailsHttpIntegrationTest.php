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

$root = dirname(__DIR__, 2);
Dotenv::createImmutable($root)->safeLoad();
$realConfig = require $root . '/config/database.php';
$realConnection = $realConfig['connections']['mysql'];
$realDatabaseName = (string) $realConnection['database'];
$disposableDatabaseName = 'directors_resale_platform_bf016_admin_' . getmypid();
bf016Assert((bool) preg_match('/^directors_resale_platform_bf016_admin_[0-9]+$/D', $disposableDatabaseName), 'Unsafe disposable test database name.');
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
    foreach ($migrations as $migration) { $testPdo->exec((string) file_get_contents($migration)); }

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

    $testPdo->exec("INSERT INTO organizations(id,name,code,organization_type,status,parent_organization_id) VALUES(4,'Partner','PARTNER','partner_agency','active',2)");
    $testPdo->exec("INSERT INTO positions(id,organization_id,name,code,status) VALUES(5,4,'Partner Admin','PARTNER_ADMIN','active')");
    $testPdo->prepare("INSERT INTO users(id,organization_id,position_id,full_name,email,status,password_hash) VALUES(5,4,5,'Partner Admin','partner@example.test','active',?)")->execute([$hash]);
    $testPdo->prepare('INSERT INTO auth_tokens(user_id,token_hash,expires_at) VALUES(5,?,?)')->execute([hash('sha256','partner'),'2999-01-01']);
    $testPdo->exec("INSERT INTO position_permissions(position_id,permission_id) SELECT p.id,q.id FROM positions p JOIN permissions q WHERE p.id IN(1,2,3,5) AND q.code IN('properties.view','properties.manage')");
    $testPdo->exec("INSERT INTO geographic_locations(id,ulid,parent_id,code,name_ar,name_en,status,provenance,type) VALUES
        (15,'00000000000000000000000015',12,'AREA','Area','Area','active','SYSTEM_ADMIN','AREA'),
        (16,'00000000000000000000000016',15,'DISTRICT','District','District','active','SYSTEM_ADMIN','DISTRICT'),
        (17,'00000000000000000000000017',10,'SKIP','Skipped District','Skipped District','active','SYSTEM_ADMIN','DISTRICT'),
        (19,'00000000000000000000000019',10,'CYCLE_A','Cycle A','Cycle A','active','SYSTEM_ADMIN','CITY'),
        (20,'00000000000000000000000020',19,'CYCLE_B','Cycle B','Cycle B','active','SYSTEM_ADMIN','AREA')");
    $testPdo->exec('UPDATE geographic_locations SET parent_id=20 WHERE id=19');
    try {
        $testPdo->exec("INSERT INTO geographic_locations(id,ulid,parent_id,code,name_ar,name_en,status,provenance,type) VALUES(18,'00000000000000000000000018',NULL,'NONROOT','City Root','City Root','active','SYSTEM_ADMIN','CITY')");
        throw new RuntimeException('Non-Country root was accepted by the schema.');
    } catch (PDOException) {}

    $create = static function(string $token) use($router): array {
        $result = bf016Request($router,'POST','/organization-properties',$token,['property_label'=>'Same display name']);
        bf016Assert($result['status']===201,'Empty Property create failed.');
        return $result['payload']['data'];
    };
    $first=$create('partner'); $second=$create('partner'); $own=$create('franchise'); $other=$create('other');
    bf016Assert($first['property_label']===$second['property_label'] && $first['property_code']!==$second['property_code'],'Duplicate display names or collision-safe code failed.');
    bf016Assert($first['property_code']==='PROP-'.$first['ulid'] && (int)$first['organization_id']===4,'Stable Organization-scoped code convention failed.');
    $stored=$testPdo->query('SELECT property_code FROM organization_properties WHERE id='.(int)$first['id'])->fetchColumn();
    bf016Assert($stored===$first['property_code'],'Code not persisted.');
    $renamed=bf016Request($router,'PUT','/organization-properties/'.$first['id'],'partner',['property_label'=>'Renamed']);
    bf016Assert($renamed['status']===200 && $renamed['payload']['data']['property_code']===$stored,'Code changed with the label.');
    bf016Assert((int)$testPdo->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='organization_properties' AND index_name='uq_organization_properties_code' AND non_unique=0")->fetchColumn()===2,'Organization/code unique index missing.');
    // MariaDB rejects generated-column assignment in strict mode and otherwise ignores it with a warning.
    try {$testPdo->exec("UPDATE organization_properties SET property_code='DUPLICATE' WHERE id=".(int)$first['id']);}catch(PDOException){}
    bf016Assert($testPdo->query('SELECT property_code FROM organization_properties WHERE id='.(int)$first['id'])->fetchColumn()===$stored,'Generated code accepted an arbitrary value.');

    $uri='/organization-properties/'.$first['id'].'/profile';
    bf016Assert(bf016Request($router,'GET',$uri)['status']===401,'Profile must require authentication.');
    bf016Assert(bf016Request($router,'GET',$uri,'partner')['payload']['data']['profile']===null,'New Property Profile must be empty.');
    foreach(['franchise','other']as$token){bf016Assert(bf016Request($router,'GET',$uri,$token)['status']===403 && bf016Request($router,'PUT',$uri,$token,['address_text'=>'Denied'])['status']===403,'Private child/unrelated scope was bypassed.');}
    bf016Assert(bf016Request($router,'GET','/organization-properties/'.$own['id'].'/profile','franchise')['status']===200,'Own Franchise access failed.');
    bf016Assert(bf016Request($router,'GET',$uri,'system')['status']===200,'System access failed.');

    $saved=bf016Request($router,'PUT',$uri,'partner',['geographic_location_id'=>16,'address_text'=>'  Street 12  ','initial_asking_price'=>'1250000.2500','currency_code'=>'EGP']);
    bf016Assert($saved['status']===200,'Administrative details save failed.');
    $aggregate=$saved['payload']['data']; $revision=$aggregate['profile']['revision'];
    bf016Assert($aggregate['profile']['address_text']==='Street 12' && $aggregate['profile']['geographic_location_id']===16 && $aggregate['profile']['initial_asking_price']==='1250000.2500' && $aggregate['profile']['currency_code']==='EGP','Address/price/currency round-trip failed.');
    bf016Assert(array_column($aggregate['geography']['ancestry']['locations'],'id')===[16,15,12,11,10],'Deepest canonical ancestry hydration failed.');
    $reloaded=bf016Request($router,'GET',$uri,'partner')['payload']['data'];
    bf016Assert($reloaded===$aggregate,'Saved administrative profile did not reload.');
    $reject=static function(array $data)use($router,$uri,&$aggregate):void{
        $result=bf016Request($router,'PUT',$uri,'partner',['expected_revision'=>$aggregate['profile']['revision']]+$data);
        bf016Assert($result['status']===422,'Invalid administrative update was accepted: '.json_encode($data));
        bf016Assert(bf016Request($router,'GET',$uri,'partner')['payload']['data']===$aggregate,'Rejected update changed saved data/revision.');
    };
    foreach([999999,13,14,18,19,20,12.5,'12.5']as$id)$reject(['geographic_location_id'=>$id]);
    foreach(['0','-1','0.0000','1.00001','1e3',1.25,'100000000000000.0000']as$price)$reject(['initial_asking_price'=>$price]);
    foreach(['eur','XXX','egp','EGP ']as$currency)$reject(['currency_code'=>$currency]);
    $reject(['initial_asking_price'=>null]); $reject(['currency_code'=>null]);
    $reject(['address_text'=>'   ']); $reject(['address_text'=>str_repeat('a',1001)]); $reject(['property_code'=>'CUSTOM']);

    $skip=bf016Request($router,'PUT',$uri,'partner',['expected_revision'=>$revision,'geographic_location_id'=>17]);
    bf016Assert($skip['status']===200 && $skip['payload']['data']['profile']['geographic_location_id']===17,'Legitimate hierarchy skip failed.');
    $aggregate=$skip['payload']['data'];
    bf016Assert($aggregate['profile']['initial_asking_price']==='1250000.2500' && $aggregate['profile']['address_text']==='Street 12','Partial geography update lost administrative fields.');

    $testPdo->exec("INSERT INTO property_categories(id,ulid,code,name_ar,name_en,status,provenance) VALUES(100,'00000000000000000000000100','ADMIN','Admin','Admin','active','SYSTEM_ADMIN')");
    $testPdo->exec("INSERT INTO unit_types(id,ulid,property_category_id,code,name_ar,name_en,status,provenance) VALUES(100,'00000000000000000000000101',100,'ADMIN_UNIT','Unit','Unit','active','SYSTEM_ADMIN')");
    $testPdo->exec("INSERT INTO unit_type_configuration_versions(id,ulid,unit_type_id,version_number,status,provenance) VALUES(100,'00000000000000000000000102',100,1,'active','SYSTEM_ADMIN')");
    $testPdo->exec("INSERT INTO measurement_definitions(id,ulid,code,name_ar,name_en,status,provenance,default_unit_code) VALUES(100,'00000000000000000000000103','ADMIN_AREA','Area','Area','active','SYSTEM_ADMIN','SQM')");
    $testPdo->exec("INSERT INTO attribute_definitions(id,ulid,code,name_ar,name_en,status,provenance,data_type) VALUES(100,'00000000000000000000000104','ADMIN_TEXT','Text','Text','active','SYSTEM_ADMIN','TEXT')");
    $testPdo->exec("INSERT INTO unit_type_measurement_rules(configuration_version_id,measurement_definition_id,requirement,is_primary,sort_order) VALUES(100,100,'OPTIONAL',0,0)");
    $testPdo->exec("INSERT INTO unit_type_attribute_rules(configuration_version_id,attribute_definition_id,requirement,sort_order) VALUES(100,100,'OPTIONAL',0)");
    $typed=bf016Request($router,'PUT',$uri,'partner',['expected_revision'=>$aggregate['profile']['revision'],'property_category_id'=>100,'unit_type_id'=>100,'measurements'=>[['definition_id'=>100,'value'=>'125.5','unit_code'=>'SQM']],'attributes'=>[['definition_id'=>100,'value'=>'Preserve this']]]);
    bf016Assert($typed['status']===200,'BF015 typed values setup failed.');
    $aggregate=$typed['payload']['data']; $oldRevision=$aggregate['profile']['revision'];
    $updated=bf016Request($router,'PUT',$uri,'system',['expected_revision'=>$oldRevision,'address_text'=>'Updated street','initial_asking_price'=>'2500000','currency_code'=>'USD']);
    bf016Assert($updated['status']===200,'System administrative update failed.');
    $newAggregate=$updated['payload']['data'];
    bf016Assert($newAggregate['measurements']===$aggregate['measurements'] && $newAggregate['attributes']===$aggregate['attributes'] && $newAggregate['profile']['revision']===$oldRevision+1,'Administrative update lost typed values or revision.');
    $aggregate=$newAggregate;
    $stale=bf016Request($router,'PUT',$uri,'partner',['expected_revision'=>$oldRevision,'address_text'=>'Stale']);
    bf016Assert($stale['status']===422 && isset($stale['payload']['error']['fields']['PROFILE_REVISION_CONFLICT']) && bf016Request($router,'GET',$uri,'partner')['payload']['data']===$aggregate,'Stale revision did not reject atomically.');
    $clear=bf016Request($router,'PUT',$uri,'partner',['expected_revision'=>$aggregate['profile']['revision'],'initial_asking_price'=>null,'currency_code'=>null,'address_text'=>null,'geographic_location_id'=>null]);
    bf016Assert($clear['status']===200 && $clear['payload']['data']['profile']['initial_asking_price']===null && $clear['payload']['data']['measurements']===$aggregate['measurements'],'Explicit clearing lost unrelated values.');
    try{$testPdo->exec('UPDATE organization_property_profiles SET initial_asking_price=0,currency_code=\'EGP\' WHERE organization_property_id='.(int)$first['id']);throw new RuntimeException('Database price guard failed.');}catch(PDOException){}

    bf016Assert(bf016Request($router,'GET','/organizations/2/basic-profile','franchise')['payload']['data']===['profile'=>null],'Empty company read envelope changed.');
    $company=bf016Request($router,'PUT','/organizations/2/basic-profile','system',['organization_name'=>'  Company Display Name  ','geographic_location_id'=>16,'address_text'=>'Company street']);
    bf016Assert($company['status']===200 && $testPdo->query('SELECT name FROM organizations WHERE id=2')->fetchColumn()==='Company Display Name','Company name/address save failed.');
    bf016Assert(bf016Request($router,'GET','/organizations/2/basic-profile','franchise')['payload']['data']['profile']['geographic_location_id']==16,'Company address failed reload.');
    $ancestry=bf016Request($router,'GET','/geographic-locations/16/ancestry','franchise');
    bf016Assert($ancestry['status']===200 && array_column($ancestry['payload']['data']['locations'],'id')===[16,15,12,11,10],'Protected company hydration ancestry failed.');
    $companyInvalid=bf016Request($router,'PUT','/organizations/2/basic-profile','system',['organization_name'=>'Must roll back','geographic_location_id'=>14]);
    bf016Assert($companyInvalid['status']===422 && $testPdo->query('SELECT name FROM organizations WHERE id=2')->fetchColumn()==='Company Display Name','Invalid company location changed its name.');
    foreach([12.5,'12.5',18,19]as$id)bf016Assert(bf016Request($router,'PUT','/organizations/2/basic-profile','franchise',['geographic_location_id'=>$id])['status']===422,'Company invalid geography accepted.');
    bf016Assert(bf016Request($router,'GET','/organizations/2/basic-profile','other')['status']===403,'Company unrelated scope bypassed.');
    bf016Assert((int)$testPdo->query('SELECT COUNT(*) FROM listings')->fetchColumn()===0,'Unexpected Listing record side effect.');
    foreach(['commission_transactions','media','private_documents']as$table){$statement=$testPdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');$statement->execute([$table]);bf016Assert((int)$statement->fetchColumn()===0,'Unexpected domain side-effect table.');}
    bf016Assert((int)$testPdo->query('SELECT COUNT(*) FROM owners')->fetchColumn()===0 && (int)$testPdo->query('SELECT COUNT(*) FROM ownerships')->fetchColumn()===0,'Property details produced Ownership side effects.');
    echo "BF016.3 Property administrative details + company address MariaDB acceptance: PASS\n";
} finally {
    if ($created) {
        bf016Assert(preg_match('/^directors_resale_platform_bf016_admin_[0-9]+$/D',$disposableDatabaseName)===1 && $disposableDatabaseName!==$realDatabaseName,'Unsafe cleanup target.');
        $serverPdo->exec("DROP DATABASE `$disposableDatabaseName`");
        $statement=$serverPdo->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');
        $statement->execute([$disposableDatabaseName]);
        bf016Assert((int)$statement->fetchColumn()===0,'Disposable cleanup could not be verified.');
    }
}
