<?php
declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Modules\Property\Seeds\AttributeDefinitionsSeedPackage;
use App\Modules\Property\Seeds\AttributeOptionsSeedPackage;
use App\Modules\Property\Seeds\MeasurementDefinitionsSeedPackage;
use App\Modules\Property\Seeds\PropertyCategoriesSeedPackage;
use App\Modules\Property\Seeds\UnitTypesSeedPackage;
use App\Modules\Property\Services\PropertyCatalogSeedRunner;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Providers\AppServiceProvider;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
function b2check(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }
function b2collision(callable $call): void { try { $call(); } catch (RuntimeException $e) { b2check($e->getMessage() === 'SEED_PACKAGE_COLLISION', 'Unexpected collision: ' . $e->getMessage()); return; } throw new RuntimeException('Expected SEED_PACKAGE_COLLISION'); }

$root = dirname(__DIR__, 2);
Dotenv\Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$c = $config['connections']['mysql'];
$name = 'directors_resale_platform_b2_seed_test_' . getmypid();
b2check(preg_match('/^directors_resale_platform_b2_seed_test_[0-9]+$/D', $name) === 1 && $name !== $c['database'], 'Unsafe database');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $c['host'], $c['port']), $c['username'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false; $db = null;
try {
    b2check(str_contains((string) $server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB required');
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created = true;
    $config['connections']['mysql']['database'] = $name; $db = new DatabaseManager($config); $pdo = $db->connection();
    $files = glob($root . '/database/migrations/*.sql'); sort($files); foreach ($files as $file) { $pdo->exec(file_get_contents($file)); }
    $box = new Container(); (new AppServiceProvider($box, []))->register(); $box->instance(DatabaseConnectionInterface::class, $db);
    $catalog = $box->make(PropertyCatalogService::class); $runner = $box->make(PropertyCatalogSeedRunner::class);
    $one = PropertyCategoriesSeedPackage::make($catalog); $two = UnitTypesSeedPackage::make($catalog); $three = MeasurementDefinitionsSeedPackage::make($catalog); $four = AttributeDefinitionsSeedPackage::make($catalog); $five = AttributeOptionsSeedPackage::make($catalog);
    $packages = [$five, $three, $one, $four, $two];
    $measurementCodes = ['BUILT_UP_AREA', 'PLOT_AREA', 'NET_AREA', 'GARDEN_AREA', 'TERRACE_AREA', 'ROOF_AREA'];
    $attributeTypes = ['BEDROOMS'=>'INTEGER','BATHROOMS'=>'INTEGER','FLOOR'=>'INTEGER','FLOORS'=>'INTEGER','PARKING_SPACES'=>'INTEGER','FURNISHING'=>'ENUM','FINISHING'=>'ENUM','VIEW'=>'TEXT','BALCONY'=>'BOOLEAN','GARDEN'=>'BOOLEAN','MAID_ROOM'=>'BOOLEAN','STORAGE'=>'BOOLEAN','DELIVERY_STATUS'=>'ENUM','DELIVERY_DATE'=>'DATE','YEAR_BUILT'=>'INTEGER'];
    $options = ['FURNISHING'=>['UNFURNISHED','SEMI_FURNISHED','FURNISHED'], 'FINISHING'=>['UNFINISHED','SEMI_FINISHED','FINISHED','LUXURY_FINISHED'], 'DELIVERY_STATUS'=>['READY','UNDER_CONSTRUCTION']];
    $snapshot = fn(): array => [$pdo->query('SELECT * FROM measurement_definitions ORDER BY id')->fetchAll(PDO::FETCH_ASSOC), $pdo->query('SELECT * FROM attribute_definitions ORDER BY id')->fetchAll(PDO::FETCH_ASSOC), $pdo->query('SELECT * FROM attribute_options ORDER BY id')->fetchAll(PDO::FETCH_ASSOC), $pdo->query('SELECT * FROM property_catalog_seed_versions ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)];
    $reset = function () use ($pdo): void { $pdo->exec('DELETE FROM property_catalog_seed_versions'); $pdo->exec('DELETE FROM attribute_options'); $pdo->exec('DELETE FROM attribute_definitions'); $pdo->exec('DELETE FROM measurement_definitions'); $pdo->exec('DELETE FROM unit_types'); $pdo->exec('DELETE FROM property_categories'); };
    $verify = function () use ($catalog, $pdo, $measurementCodes, $attributeTypes, $options, $one, $two, $three, $four, $five): void {
        $measurements = $catalog->listMeasurementDefinitions(['limit'=>100]); b2check(count($measurements) === 6, 'Measurement count');
        b2check(array_column($measurements, 'code') === $measurementCodes, 'Measurement codes/order');
        foreach ($measurements as $row) { b2check($row['default_unit_code'] === 'SQM' && $row['provenance'] === 'SYSTEM_SEED' && $row['status'] === 'active', 'Measurement shape'); }
        $attributes = $catalog->listAttributeDefinitions(['limit'=>100]); b2check(count($attributes) === 15, 'Attribute count');
        $byCode = []; foreach ($attributes as $row) { $byCode[$row['code']] = $row; b2check($row['provenance'] === 'SYSTEM_SEED' && $row['status'] === 'active' && $row['data_type'] === $attributeTypes[$row['code']], 'Attribute shape'); }
        b2check(array_keys($byCode) === array_keys($attributeTypes), 'Attribute codes/order');
        $total = 0; foreach ($options as $definition => $codes) { $rows = $catalog->listAttributeOptions($byCode[$definition]['id'], ['limit'=>100]); $total += count($rows); b2check(array_column($rows, 'code') === $codes, 'Option mapping/order'); foreach ($rows as $index => $row) { b2check($row['provenance'] === 'SYSTEM_SEED' && $row['status'] === 'active' && (int)$row['sort_order'] === $index + 1, 'Option shape'); } }
        b2check($total === 9, 'Option count'); foreach ($attributes as $row) { if ($row['data_type'] !== 'ENUM') { b2check($catalog->listAttributeOptions($row['id']) === [], 'Options on non-enum'); } }
        b2check(array_slice(array_column($pdo->query('SELECT seed_key FROM property_catalog_seed_versions ORDER BY id')->fetchAll(PDO::FETCH_ASSOC), 'seed_key'), 0, 5) === [$one->key,$two->key,$three->key,$four->key,$five->key], 'Ledger order');
    };
    $keys = array_map(fn($p) => $p->key, [$one,$two,$three,$four,$five]); $orders = array_map(fn($p) => $p->order, [$one,$two,$three,$four,$five]); b2check($orders === [1,2,3,4,5] && count(array_unique($keys)) === 5, 'Package keys/orders'); foreach ([$three,$four,$five] as $p) { b2check(preg_match('/^[0-9a-f]{64}$/D', $p->checksum()) === 1 && $p->checksum() === $p->checksum(), 'Checksum'); }
    $applied = []; foreach ([$one,$two,$three,$four,$five] as $p) { $applied[] = 'APPLY '.$p->key; $applied[] = 'DONE '.$p->key; }
    $skipped = array_map(fn($p) => 'SKIP '.$p->key, [$one,$two,$three,$four,$five]);
    b2check($runner->run($packages) === $applied, 'Fresh order'); $verify(); $before = $snapshot(); b2check($runner->run($packages) === $skipped && $snapshot() === $before, 'Immediate rerun');
    $measurement = $catalog->listMeasurementDefinitions()[0]; $catalog->updateMeasurementDefinition($measurement['id'], ['name_en'=>'Admin edit']); $modified = $snapshot(); b2check($runner->run($packages) === $skipped && $snapshot() === $modified, 'Admin edit overwritten');
    $reset(); $runner->run([$one,$two]); $catalog->createMeasurementDefinition(['code'=>'BUILT_UP_AREA','name_ar'=>'Admin','name_en'=>'Admin','default_unit_code'=>'SQM']); $before = $snapshot(); b2collision(fn() => $runner->run([$three])); b2check($snapshot() === $before, 'Measurement admin collision/ledger');
    $reset(); $runner->run([$one,$two]); $catalog->createMeasurementDefinition(['code'=>'BUILT_UP_AREA','name_ar'=>'Incompatible','name_en'=>'BUILT_UP_AREA','default_unit_code'=>'SQM','sort_order'=>1,'provenance'=>'SYSTEM_SEED']); $before = $snapshot(); b2collision(fn() => $runner->run([$three])); b2check($snapshot() === $before, 'Measurement seeded shape collision');
    $reset(); $runner->run([$one,$two,$three]); $catalog->createAttributeDefinition(['code'=>'YEAR_BUILT','name_ar'=>'Admin','name_en'=>'Admin','data_type'=>'INTEGER']); $before = $snapshot(); b2collision(fn() => $runner->run([$four])); b2check($snapshot() === $before, 'Attribute atomic collision');
    $reset(); $runner->run([$one,$two,$three,$four]); $defs = array_column($catalog->listAttributeDefinitions(), 'id', 'code'); $catalog->createAttributeOption($defs['DELIVERY_STATUS'], ['code'=>'READY','name_ar'=>'Admin','name_en'=>'Admin']); $before = $snapshot(); b2collision(fn() => $runner->run([$five])); b2check($snapshot() === $before, 'Option atomic collision');
    $reset(); $catalog->createMeasurementDefinition(['code'=>'BUILT_UP_AREA','name_ar'=>'BUILT_UP_AREA','name_en'=>'BUILT_UP_AREA','default_unit_code'=>'SQM','sort_order'=>1,'provenance'=>'SYSTEM_SEED']); $catalog->createAttributeDefinition(['code'=>'BEDROOMS','name_ar'=>'BEDROOMS','name_en'=>'BEDROOMS','data_type'=>'INTEGER','sort_order'=>1,'provenance'=>'SYSTEM_SEED']); $catalog->createAttributeDefinition(['code'=>'FURNISHING','name_ar'=>'FURNISHING','name_en'=>'FURNISHING','data_type'=>'ENUM','sort_order'=>6,'provenance'=>'SYSTEM_SEED']); $furnishing = array_column($catalog->listAttributeDefinitions(), 'id', 'code')['FURNISHING']; $catalog->createAttributeOption($furnishing, ['code'=>'UNFURNISHED','name_ar'=>'UNFURNISHED','name_en'=>'UNFURNISHED','sort_order'=>1,'provenance'=>'SYSTEM_SEED']); $runner->run($packages); $verify();
    $reset();
    $cli = function () use ($root, $name, $c): array { $env=['DB_CONNECTION'=>'mysql','DB_DATABASE'=>$name,'DB_HOST'=>$c['host'],'DB_PORT'=>$c['port'],'DB_USERNAME'=>$c['username'],'DB_PASSWORD'=>$c['password']]; $code='$_ENV = array_replace($_ENV, '.var_export($env,true).'); require '.var_export($root.'/bin/seed-property-catalog.php',true).';'; $process=proc_open([PHP_BINARY,'-r',$code],[1=>['pipe','w'],2=>['pipe','w']],$pipes,$root); b2check(is_resource($process),'CLI unavailable'); $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);b2check(proc_close($process)===0 && $err==='','CLI failure');return preg_split('/\R/',trim($out)); };
    b2check(array_slice($cli(), 0, count($applied)) === $applied, 'CLI first run'); $verify(); b2check(array_slice($cli(), 0, count($skipped)) === $skipped, 'CLI second run');
    echo "BF014 B2 production packages + isolated CLI MariaDB acceptance: PASS\n";
} finally { if ($db !== null && $db->connection()->inTransaction()) { $db->rollback(); } if ($created) { $server->exec("DROP DATABASE `$name`"); } }
