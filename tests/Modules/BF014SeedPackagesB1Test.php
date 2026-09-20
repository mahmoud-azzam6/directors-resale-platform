<?php
declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Modules\Property\Services\PropertyCatalogSeedRunner;
use App\Modules\Property\Seeds\PropertyCategoriesSeedPackage;
use App\Modules\Property\Seeds\UnitTypesSeedPackage;
use App\Providers\AppServiceProvider;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
function b1check(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
function b1collision(callable $call): void {
    try { $call(); } catch (RuntimeException $e) {
        b1check($e->getMessage() === 'SEED_PACKAGE_COLLISION', 'Unexpected collision error: ' . $e->getMessage());
        return;
    }
    throw new RuntimeException('Expected SEED_PACKAGE_COLLISION');
}
$root = dirname(__DIR__, 2);
Dotenv\Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$c = $config['connections']['mysql'];
$name = 'directors_resale_platform_b1_seed_test_' . getmypid();
b1check(preg_match('/^directors_resale_platform_b1_seed_test_[0-9]+$/D', $name) === 1 && $name !== $c['database'], 'Unsafe database');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $c['host'], $c['port']), $c['username'], $c['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$created = false;
$db = null;
try {
    b1check(str_contains((string)$server->query('SELECT VERSION()')->fetchColumn(), 'MariaDB'), 'Real MariaDB required');
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $config['connections']['mysql']['database'] = $name;
    $db = new DatabaseManager($config);
    $pdo = $db->connection();
    $files = glob($root . '/database/migrations/*.sql');
    sort($files);
    foreach ($files as $file) { $pdo->exec(file_get_contents($file)); }
    $box = new Container();
    (new AppServiceProvider($box, []))->register();
    $box->instance(DatabaseConnectionInterface::class, $db);
    $catalog = $box->make(PropertyCatalogService::class);
    $runner = $box->make(PropertyCatalogSeedRunner::class);
    $one = PropertyCategoriesSeedPackage::make($catalog);
    $two = UnitTypesSeedPackage::make($catalog);
    $packages = [$two, $one];
    $snapshot = fn(): array => [
        $pdo->query('SELECT * FROM property_categories ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query('SELECT * FROM unit_types ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query('SELECT * FROM property_catalog_seed_versions ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    ];
    $reset = function () use ($pdo): void {
        $pdo->exec('DELETE FROM property_catalog_seed_versions');
        $pdo->exec('DELETE FROM unit_types');
        $pdo->exec('DELETE FROM property_categories');
    };
    $expected = [
        'RESIDENTIAL'=>['APARTMENT','DUPLEX','PENTHOUSE','STUDIO','VILLA','TOWNHOUSE','TWIN_HOUSE','CHALET'],
        'COMMERCIAL'=>['RETAIL_SHOP','COMMERCIAL_UNIT','WAREHOUSE'],
        'ADMINISTRATIVE'=>['OFFICE','ADMINISTRATIVE_UNIT'],
        'MEDICAL'=>['CLINIC','MEDICAL_CENTER'],
        'LAND'=>['RESIDENTIAL_LAND','COMMERCIAL_LAND','AGRICULTURAL_LAND','INDUSTRIAL_LAND'],
        'OTHER'=>['OTHER'],
    ];
    $verify = function () use ($snapshot, $expected, $one, $two): void {
        [$categories, $units, $ledger] = $snapshot();
        b1check(count($categories) === 6 && count($units) === 20, 'Exact baseline counts');
        $codes = array_column($categories, 'code'); sort($codes);
        $wanted = array_keys($expected); sort($wanted);
        b1check($codes === $wanted, 'Exact Category codes');
        $byId = [];
        foreach ($categories as $row) {
            b1check($row['provenance'] === 'SYSTEM_SEED' && $row['status'] === 'active', 'Category provenance/status');
            $byId[$row['id']] = $row['code'];
        }
        $mapping = [];
        foreach ($expected as $category => $types) { foreach ($types as $type) { $mapping[$type] = $category; } }
        $actual = [];
        foreach ($units as $row) {
            b1check($row['provenance'] === 'SYSTEM_SEED' && $row['status'] === 'active', 'Unit Type provenance/status');
            $actual[$row['code']] = $byId[$row['property_category_id']];
        }
        ksort($actual); ksort($mapping);
        b1check($actual === $mapping, 'Exact Unit Type codes and Category mappings');
        b1check(array_column($ledger, 'seed_key') === [$one->key, $two->key], 'Ledger order');
        b1check(array_column($ledger, 'checksum') === [$one->checksum(), $two->checksum()], 'Ledger checksums');
    };
    b1check($one->order < $two->order && $one->key !== $two->key, 'Distinct keys/orders');
    foreach ([$one, $two] as $p) { b1check(preg_match('/^[0-9a-f]{64}$/D', $p->checksum()) === 1, 'SHA-256 format'); }
    $applied = ['APPLY '.$one->key, 'DONE '.$one->key, 'APPLY '.$two->key, 'DONE '.$two->key];
    $skipped = ['SKIP '.$one->key, 'SKIP '.$two->key];
    b1check($runner->run($packages) === $applied, 'Numeric execution order');
    $verify();
    $before = $snapshot();
    b1check($runner->run($packages) === $skipped && $snapshot() === $before, 'Immediate rerun mutation');
    $category = $catalog->listCategories()[0];
    $catalog->updateCategory($category['id'], ['name_en'=>'Admin revised label']);
    $unit = $catalog->listUnitTypes()[0];
    $catalog->deactivateUnitType($unit['id']);
    $modified = $snapshot();
    b1check($runner->run($packages) === $skipped && $snapshot() === $modified, 'Admin edits overwritten');
    b1check(PropertyCategoriesSeedPackage::make($catalog)->checksum() === $one->checksum() && UnitTypesSeedPackage::make($catalog)->checksum() === $two->checksum(), 'Runtime-dependent checksum');
    $reset();
    $catalog->createCategory(['code'=>'OTHER','name_ar'=>'Admin','name_en'=>'Admin']);
    $before = $snapshot();
    b1collision(fn() => $runner->run($packages));
    b1check($snapshot() === $before, 'Category collision takeover/partial data/ledger');
    $reset();
    $recovery = $catalog->createCategory(['code'=>'RESIDENTIAL','name_ar'=>'Residential','name_en'=>'Residential','provenance'=>'SYSTEM_SEED']);
    $runner->run($packages);
    $verify();
    b1check($catalog->findCategory($recovery['id']) === $recovery, 'Recovery row overwritten');
    $reset();
    $runner->run([$one]);
    $categories = array_column($catalog->listCategories(), 'id', 'code');
    $catalog->createUnitType(['code'=>'OTHER','name_ar'=>'Conflict','name_en'=>'Conflict','property_category_id'=>$categories['RESIDENTIAL'],'provenance'=>'SYSTEM_SEED']);
    $before = $snapshot();
    b1collision(fn() => $runner->run($packages));
    b1check($snapshot() === $before, 'Package 002 not atomic or prior package lost');
    b1check(PropertyCategoriesSeedPackage::make($catalog)->checksum() === $one->checksum() && UnitTypesSeedPackage::make($catalog)->checksum() === $two->checksum(), 'Checksum changed with IDs');
    $reset();
    // CLI wrapper sets only the child process configuration before the real entry point.
    // Dotenv immutable loading preserves these explicit values; normal DB is never selected.
    $cli = function () use ($root, $name, $c): array {
        $env = ['DB_CONNECTION'=>'mysql','DB_DATABASE'=>$name,'DB_HOST'=>$c['host'],'DB_PORT'=>$c['port'],'DB_USERNAME'=>$c['username'],'DB_PASSWORD'=>$c['password']];
        $code = '$_ENV = array_replace($_ENV, ' . var_export($env, true) . '); require ' . var_export($root . '/bin/seed-property-catalog.php', true) . ';';
        $process = proc_open([PHP_BINARY, '-r', $code], [1=>['pipe','w'],2=>['pipe','w']], $pipes, $root);
        b1check(is_resource($process), 'CLI process unavailable');
        $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $exit = proc_close($process);
        b1check($exit === 0 && $stderr === '', 'Isolated CLI failed');
        return preg_split('/\R/', trim($stdout));
    };
    b1check($cli() === $applied, 'CLI first run output');
    $verify();
    $before = $snapshot();
    b1check($cli() === $skipped && $snapshot() === $before, 'CLI second run');
    echo "BF014 B1 production packages + isolated CLI MariaDB acceptance: PASS\n";
} finally {
    if ($db !== null && $db->connection()->inTransaction()) { $db->rollback(); }
    if ($created) { $server->exec("DROP DATABASE `$name`"); }
}
