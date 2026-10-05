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
$disposableDatabaseName = 'directors_resale_platform_bf016_image_' . getmypid();
bf016Assert((bool) preg_match('/^directors_resale_platform_bf016_image_[0-9]+$/D', $disposableDatabaseName), 'Unsafe disposable test database name.');
bf016Assert($disposableDatabaseName !== $realDatabaseName, 'Disposable cleanup target must never be the configured database.');
$serverPdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $realConnection['host'], $realConnection['port']), (string) $realConnection['username'], (string) $realConnection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
$imageRoot = sys_get_temp_dir().'/bf0165-images-'.getmypid().'-'.bin2hex(random_bytes(4));
$sourcePath = $imageRoot.'-source.png';
$servingProbePath = $root.'/public/bf0165-acceptance-'.bin2hex(random_bytes(16)).'.php';
$oldStorage = $_ENV['PROPERTY_IMAGE_STORAGE_PATH'] ?? null;
$_ENV['PROPERTY_IMAGE_STORAGE_PATH'] = $imageRoot;

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

    $property=bf016Request($router,'POST','/organization-properties','partner',['property_label'=>'Image test'])['payload']['data'];
    $id=(int)$property['id'];
    $profileUri='/organization-properties/'.$id.'/profile';
    $saved=bf016Request($router,'PUT',$profileUri,'partner',['geographic_location_id'=>16,'address_text'=>'Preserve address','initial_asking_price'=>'123456','currency_code'=>'EGP']);
    bf016Assert($saved['status']===200,'Administrative setup failed.');
    $uri=$profileUri; $aggregate=$saved['payload']['data'];
    $testPdo->exec("INSERT INTO property_categories(id,ulid,code,name_ar,name_en,status,provenance) VALUES(100,'00000000000000000000000100','ADMIN','Admin','Admin','active','SYSTEM_ADMIN')");
    $testPdo->exec("INSERT INTO unit_types(id,ulid,property_category_id,code,name_ar,name_en,status,provenance) VALUES(100,'00000000000000000000000101',100,'ADMIN_UNIT','Unit','Unit','active','SYSTEM_ADMIN')");
    $testPdo->exec("INSERT INTO unit_type_configuration_versions(id,ulid,unit_type_id,version_number,status,provenance) VALUES(100,'00000000000000000000000102',100,1,'active','SYSTEM_ADMIN')");
    $testPdo->exec("INSERT INTO measurement_definitions(id,ulid,code,name_ar,name_en,status,provenance,default_unit_code) VALUES(100,'00000000000000000000000103','ADMIN_AREA','Area','Area','active','SYSTEM_ADMIN','SQM')");
    $testPdo->exec("INSERT INTO attribute_definitions(id,ulid,code,name_ar,name_en,status,provenance,data_type) VALUES(100,'00000000000000000000000104','ADMIN_TEXT','Text','Text','active','SYSTEM_ADMIN','TEXT')");
    $testPdo->exec("INSERT INTO unit_type_measurement_rules(configuration_version_id,measurement_definition_id,requirement,is_primary,sort_order) VALUES(100,100,'OPTIONAL',0,0)");
    $testPdo->exec("INSERT INTO unit_type_attribute_rules(configuration_version_id,attribute_definition_id,requirement,sort_order) VALUES(100,100,'OPTIONAL',0)");
    $typed=bf016Request($router,'PUT',$uri,'partner',['expected_revision'=>$aggregate['profile']['revision'],'property_category_id'=>100,'unit_type_id'=>100,'measurements'=>[['definition_id'=>100,'value'=>'125.5','unit_code'=>'SQM']],'attributes'=>[['definition_id'=>100,'value'=>'Preserve this']]]);
    bf016Assert($typed['status']===200,'BF015 typed values setup failed.');
    $before=bf016Request($router,'GET',$profileUri,'partner')['payload']['data'];
    $testPdo->exec("INSERT INTO owners(ulid,organization_id,party_type,display_name,status,created_by_user_id,updated_by_user_id) VALUES('00000000000000000000000200',4,'individual','Preserve owner','active',5,5)");
    $testPdo->exec("INSERT INTO ownerships(ulid,organization_property_id,organization_id,status,created_by_user_id,updated_by_user_id) VALUES('00000000000000000000000201',$id,4,'current',5,5)");
    $ownershipBefore=$testPdo->query('SELECT * FROM ownerships')->fetchAll(PDO::FETCH_ASSOC);
    $ownersBefore=$testPdo->query('SELECT * FROM owners')->fetchAll(PDO::FETCH_ASSOC);
    $uri='/organization-properties/'.$id.'/primary-image';
    $upload=static function(?string $token,?App\Http\UploadedFile $file,array $data=[],?string $target=null)use($router,$uri):Response{
        $target=$target??$uri;
        $request=new Request(new InputBag([]),new InputBag([]),new InputBag($data),new HeaderBag($token===null?[]:['Authorization'=>'Bearer '.$token]),new InputBag([]),new InputBag([]),$file===null?[]:['image'=>$file],'POST',$target);
        return $router->dispatch('POST',$target,$request);
    };
    bf016Assert(bf016Request($router,'GET',$uri,'partner')['payload']['data']===['primary_image'=>null],'Empty image envelope failed.');
    $raw=imagecreatetruecolor(600,600); imagepng($raw,$sourcePath); imagedestroy($raw);
    file_put_contents($sourcePath,'PRIVATE_METADATA_TRAILER',FILE_APPEND);
    $file=new App\Http\UploadedFile('../../unsafe.php','application/x-php',$sourcePath,UPLOAD_ERR_OK,filesize($sourcePath));
    $first=bf016Response($upload('partner',$file));
    bf016Assert($first['status']===200,'Primary image upload failed: '.json_encode($first));
    $image=$first['payload']['data']['primary_image'];
    bf016Assert(preg_match('/^[a-f0-9]{32}$/D',$image['reference'])===1 && $image['mime_type']==='image/webp','Unsafe public reference.');
    $files=glob($imageRoot.'/*.webp');
    bf016Assert(count($files)===1 && !str_contains(file_get_contents($files[0]),'PRIVATE_METADATA_TRAILER'),'Metadata was retained or multiple images created.');
    bf016Assert(bf016Request($router,'GET',$uri,'partner')['payload']['data']['primary_image']===$image,'Metadata failed hydration.');
    foreach([null,'other','franchise']as$token){
        $expected=$token===null?401:403;
        bf016Assert(bf016Request($router,'GET',$uri,$token)['status']===$expected && bf016Request($router,'GET',$uri.'/content',$token)['status']===$expected && bf016Response($upload($token,$file))['status']===$expected && bf016Request($router,'DELETE',$uri,$token)['status']===$expected,'Private image scope bypass.');
    }
    bf016Assert(bf016Request($router,'GET',$uri,'system')['status']===200,'System access failed.');
    $franchiseProperty=bf016Request($router,'POST','/organization-properties','franchise',['property_label'=>'Franchise image'])['payload']['data'];
    $franchiseUri='/organization-properties/'.$franchiseProperty['id'].'/primary-image';
    bf016Assert(bf016Response($upload('franchise',$file,[],$franchiseUri))['status']===200 && bf016Request($router,'GET',$franchiseUri,'franchise')['status']===200,'Own Franchise image access failed.');
    bf016Assert(bf016Request($router,'GET',$franchiseUri,'catalogless')['status']===403 && bf016Response($upload('catalogless',$file,[],$franchiseUri))['status']===403 && bf016Request($router,'DELETE',$franchiseUri,'catalogless')['status']===403,'Missing capabilities allowed own-Organization image access.');
    bf016Assert(bf016Request($router,'DELETE',$franchiseUri,'franchise')['status']===200 && count(glob($imageRoot.'/*.webp'))===1,'Own Franchise image cleanup failed.');
    $_ENV['PROPERTY_IMAGE_STORAGE_PATH']=$root;
    try { try { $upload('partner',$file); throw new LogicException('Repository storage was accepted.'); } catch (RuntimeException $e) { bf016Assert(str_contains($e->getMessage(),'outside repository'),'Wrong unsafe-storage rejection.'); } }
    finally { $_ENV['PROPERTY_IMAGE_STORAGE_PATH']=$imageRoot; }
    $testPdo->exec("CREATE TRIGGER reject_primary_image_update BEFORE UPDATE ON property_primary_images FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='simulated persistence failure'");
    try {
        try { $upload('partner',$file); throw new RuntimeException('Failed persistence unexpectedly succeeded.'); }
        catch (PDOException) {}
        bf016Assert(count(glob($imageRoot.'/*.webp'))===1 && bf016Request($router,'GET',$uri,'partner')['payload']['data']['primary_image']===$image,'Failed transaction left a file or changed the image.');
    } finally { $testPdo->exec('DROP TRIGGER reject_primary_image_update'); }
    $contentRequest=new Request(new InputBag([]),new InputBag([]),new InputBag([]),new HeaderBag(['Authorization'=>'Bearer partner']),new InputBag([]),new InputBag([]),[],'GET',$uri.'/content');
    $content=$router->dispatch('GET',$uri.'/content',$contentRequest);
    $reflection=new ReflectionProperty(Response::class,'imageBytes'); $reflection->setAccessible(true); $bytes=$reflection->getValue($content);
    bf016Assert(str_starts_with($bytes,'RIFF') && str_contains(substr($bytes,0,16),'WEBP'),'Protected content failed.');
    $second=bf016Response($upload('system',$file))['payload']['data']['primary_image'];
    bf016Assert($second['reference']!==$image['reference'] && count(glob($imageRoot.'/*.webp'))===1 && !is_file($imageRoot.'/'.$image['reference'].'.webp'),'Replacement retained a gallery.');
    foreach([
        new App\Http\UploadedFile('bad.png','image/png',$sourcePath,UPLOAD_ERR_OK,10485761),
        new App\Http\UploadedFile('bad.png','image/png',$sourcePath,UPLOAD_ERR_PARTIAL,filesize($sourcePath)),
    ]as$invalid)bf016Assert(bf016Response($upload('partner',$invalid))['status']===422,'Invalid size/error accepted.');
    bf016Assert(bf016Response($upload('partner',$file,['reference'=>'../../outside']))['status']===422,'Client storage reference accepted.');
    // Full decode, static-image and pixel-limit validation, independent of filename/MIME claims.
    $png=file_get_contents($sourcePath);
    foreach([substr($png,0,40),$png.'acTL']as$invalidBytes) {
        file_put_contents($sourcePath,$invalidBytes); clearstatcache(true,$sourcePath);
        bf016Assert(bf016Response($upload('partner',new App\Http\UploadedFile('bad.png','image/png',$sourcePath,0,filesize($sourcePath))))['status']===422,'Broken/animated image accepted.');
    }
    $small=imagecreatetruecolor(599,600); imagepng($small,$sourcePath); imagedestroy($small); clearstatcache(true,$sourcePath);
    bf016Assert(bf016Response($upload('partner',new App\Http\UploadedFile('small.png','image/png',$sourcePath,0,filesize($sourcePath))))['status']===422,'Undersized image accepted.');
    // A JPEG with orientation and private EXIF metadata must become metadata-free oriented WebP.
    $jpeg=imagecreatetruecolor(600,800); imagejpeg($jpeg,$sourcePath); imagedestroy($jpeg);
    $jpegBytes=file_get_contents($sourcePath);
    $tiff="II\x2A\x00\x08\x00\x00\x00".pack('v',1).pack('vvVv',0x0112,3,1,6)."\x00\x00".pack('V',0).'PRIVATE_GPS_METADATA';
    $exif="Exif\x00\x00".$tiff;
    file_put_contents($sourcePath,substr($jpegBytes,0,2)."\xFF\xE1".pack('n',strlen($exif)+2).$exif.substr($jpegBytes,2)); clearstatcache(true,$sourcePath);
    $oriented=bf016Response($upload('partner',new App\Http\UploadedFile('oriented.jpg','image/jpeg',$sourcePath,0,filesize($sourcePath))));
    bf016Assert($oriented['status']===200 && $oriented['payload']['data']['primary_image']['width']===800 && $oriented['payload']['data']['primary_image']['height']===600,'JPEG orientation failed.');
    $storedBytes=file_get_contents(glob($imageRoot.'/*.webp')[0]);
    bf016Assert(!str_contains($storedBytes,'Exif')&&!str_contains($storedBytes,'PRIVATE_GPS_METADATA')&&!str_contains($storedBytes,'EXIF'),'JPEG EXIF/GPS metadata retained.');
    // WebP input is accepted and re-encoded; originals remain temporary.
    file_put_contents($sourcePath,$storedBytes); clearstatcache(true,$sourcePath);
    bf016Assert(bf016Response($upload('partner',new App\Http\UploadedFile('safe.webp','image/webp',$sourcePath,0,filesize($sourcePath))))['status']===200,'WebP input failed.');
    file_put_contents($sourcePath,'<svg xmlns="http://www.w3.org/2000/svg"/>'); clearstatcache(true,$sourcePath);
    bf016Assert(bf016Response($upload('partner',new App\Http\UploadedFile('x.png','image/png',$sourcePath,0,filesize($sourcePath))))['status']===422,'Spoofed SVG accepted.');
    bf016Assert(bf016Request($router,'DELETE',$uri,'partner')['status']===200 && count(glob($imageRoot.'/*.webp'))===0,'Removal left stored file.');
    bf016Assert(bf016Request($router,'GET',$uri,'partner')['payload']['data']===['primary_image'=>null] && bf016Request($router,'GET',$uri.'/content','partner')['status']===404,'Removal/empty delivery failed.');
    bf016Assert(bf016Request($router,'GET',$profileUri,'partner')['payload']['data']===$before,'Image mutation changed Property/Profile administrative data.');
    bf016Assert((int)$testPdo->query('SELECT COUNT(*) FROM property_primary_images')->fetchColumn()===0,'Removal left metadata.');
    $raw=imagecreatetruecolor(600,600); imagepng($raw,$sourcePath); imagedestroy($raw); clearstatcache(true,$sourcePath);
    $valid=new App\Http\UploadedFile('valid.png','image/png',$sourcePath,0,filesize($sourcePath));
    $testPdo->exec("UPDATE organization_properties SET status='archived',archived_at=NOW(),archived_by_user_id=5 WHERE id=$id");
    bf016Assert(bf016Response($upload('partner',$valid))['status']===422 && count(glob($imageRoot.'/*.webp'))===0,'Archived mutation left a processed orphan.');
    $testPdo->exec("UPDATE organization_properties SET status='active',archived_at=NULL,archived_by_user_id=NULL WHERE id=$id");
    bf016Assert($testPdo->query('SELECT * FROM owners')->fetchAll(PDO::FETCH_ASSOC)===$ownersBefore && $testPdo->query('SELECT * FROM ownerships')->fetchAll(PDO::FETCH_ASSOC)===$ownershipBefore,'Image mutation changed existing Owner/Ownership records.');
    // Exercise Apache's actual multipart normalization and protected binary response on this disposable DB.
    $entry='<?php require '.var_export($root.'/vendor/autoload.php',true).'; Dotenv\\Dotenv::createImmutable('.var_export($root,true).')->safeLoad(); $cfg=require '.var_export($root.'/config/database.php',true).'; $cfg["connections"]["mysql"]["database"]='.var_export($disposableDatabaseName,true).'; $_ENV["PROPERTY_IMAGE_STORAGE_PATH"]='.var_export($imageRoot,true).'; $db=new App\\Core\\DatabaseManager($cfg); $c=new App\\Core\\Container(); (new App\\Providers\\AppServiceProvider($c,[]))->register(); $c->instance(App\\Core\\Database\\DatabaseConnectionInterface::class,$db); $r=new App\\Routing\\Router(); (require '.var_export($root.'/routes/api.php',true).')($r,$c,[]); $q=(new App\\Http\\Kernel())->createRequest(); $r->dispatch($q->method(),'.var_export($uri,true).'.($_GET["content"]??false?"/content":""),$q)->send();';
    file_put_contents($servingProbePath,$entry);
    $url='http://127.0.0.1/directors-resale-platform/public/'.basename($servingProbePath);
    $curl=curl_init($url); curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>['Authorization: Bearer partner'],CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>['image'=>new CURLFile($sourcePath,'image/png','source.png')]]);
    $httpBytes=curl_exec($curl); $httpCode=curl_getinfo($curl,CURLINFO_RESPONSE_CODE); curl_close($curl);
    bf016Assert($httpCode===200 && json_decode($httpBytes,true)['data']['primary_image']['mime_type']==='image/webp','Apache multipart acceptance failed: '.$httpBytes);
    $curl=curl_init($url.'?content=1'); curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>['Authorization: Bearer partner']]);
    $httpBytes=curl_exec($curl); $type=curl_getinfo($curl,CURLINFO_CONTENT_TYPE); curl_close($curl);
    bf016Assert($type==='image/webp' && str_starts_with($httpBytes,'RIFF'),'Apache binary delivery failed.');
    bf016Assert(bf016Request($router,'DELETE',$uri,'partner')['status']===200,'Apache-uploaded image cleanup failed.');
    foreach(['listings','requests','deals','marketplace','commission_transactions','private_documents']as$table){$s=$testPdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');$s->execute([$table]);bf016Assert((int)$s->fetchColumn()===0,'Unexpected domain side effect.');}
    echo "BF016.5 primary image MariaDB acceptance: PASS\n";
} finally {
    if ($created) {
        bf016Assert(preg_match('/^directors_resale_platform_bf016_image_[0-9]+$/D',$disposableDatabaseName)===1 && $disposableDatabaseName!==$realDatabaseName,'Unsafe cleanup target.');
        $serverPdo->exec("DROP DATABASE `$disposableDatabaseName`");
        $s=$serverPdo->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');$s->execute([$disposableDatabaseName]);bf016Assert((int)$s->fetchColumn()===0,'Database cleanup failed.');
    }
    if(is_file($sourcePath))unlink($sourcePath);
    if(is_file($servingProbePath))unlink($servingProbePath);
    foreach(glob($imageRoot.'/*.webp')?:[]as$file)unlink($file);
    if(is_dir($imageRoot))rmdir($imageRoot);
    if($oldStorage===null)unset($_ENV['PROPERTY_IMAGE_STORAGE_PATH']);else $_ENV['PROPERTY_IMAGE_STORAGE_PATH']=$oldStorage;
    bf016Assert(!file_exists($sourcePath)&&!is_dir($imageRoot)&&!file_exists($servingProbePath),'Disposable file cleanup failed.');
}
