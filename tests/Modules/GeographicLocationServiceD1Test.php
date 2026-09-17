<?php
declare(strict_types=1);
use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Exceptions\ValidationException;
use App\Modules\Property\Services\GeographicLocationService;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;
require dirname(__DIR__, 2) . '/vendor/autoload.php';
function d1ok(bool $ok, string $message): void { if (! $ok) throw new RuntimeException($message); }
function d1code(callable $call, string $code): void { try { $call(); } catch (ValidationException $e) { d1ok(isset($e->errors()[$code]), 'Expected ' . $code); return; } throw new RuntimeException('Expected ' . $code); }
final class D1LockTrace
{
    public array $ids = [];
    public $beforeTransaction = null;
}
final class D1Statement extends PDOStatement
{
    private array $values = [];
    protected function __construct(private D1LockTrace $trace) {}
    public function bindValue($param, $value, $type = PDO::PARAM_STR): bool
    {
        $this->values[$param] = $value;
        return parent::bindValue($param, $value, $type);
    }
    public function execute(?array $params = null): bool
    {
        if (str_contains($this->queryString, 'FOR UPDATE')) { $this->trace->ids[] = (int) reset($this->values); }
        return parent::execute($params);
    }
}
final class D1Database implements DatabaseConnectionInterface
{
    public function __construct(private DatabaseManager $inner, private D1LockTrace $trace) {}
    public function connection(): PDO { return $this->inner->connection(); }
    public function beginTransaction(): void { $this->inner->beginTransaction(); }
    public function commit(): void { $this->inner->commit(); }
    public function rollback(): void { $this->inner->rollback(); }
    public function transaction(callable $callback): mixed
    {
        // A deterministic committed change between discovery and root locking;
        // no additional process or concurrent connection is involved.
        $hook = $this->trace->beforeTransaction;
        $this->trace->beforeTransaction = null;
        if ($hook !== null) { $hook($this->connection()); }
        return $this->inner->transaction($callback);
    }
}
function d1locks(D1LockTrace $trace, callable $call, array $expected): void
{
    $trace->ids = [];
    $call();
    d1ok($trace->ids === $expected, 'Lock order: ' . json_encode($trace->ids) . ' expected ' . json_encode($expected));
}
$root = dirname(__DIR__, 2); Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php'; $connection = $config['connections']['mysql'];
$name = 'directors_resale_platform_d1_test_' . getmypid();
d1ok(preg_match('/^directors_resale_platform_d1_test_[0-9]+$/D', $name) === 1 && $name !== $connection['database'], 'Unsafe database');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$made = false; $database = null;
try {
    $server->exec("CREATE DATABASE $name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $made = true;
    $config['connections']['mysql']['database'] = $name; $database = new DatabaseManager($config);
    $files = glob($root . '/database/migrations/*.sql') ?: []; sort($files);
    foreach ($files as $file) $database->connection()->exec(file_get_contents($file));
    $trace = new D1LockTrace();
    $database->connection()->setAttribute(PDO::ATTR_STATEMENT_CLASS, [D1Statement::class, [$trace]]);
    $container = new Container(); (new AppServiceProvider($container, []))->register(); $container->instance(DatabaseConnectionInterface::class, new D1Database($database, $trace));
    $service = $container->make(GeographicLocationService::class);
    $country = $service->createLocation(['code'=>' eg ','name_ar'=>'Egypt','name_en'=>'Egypt','location_type'=>'COUNTRY']);
    $governorate = $service->createLocation(['code'=>'cairo','name_ar'=>'Cairo','name_en'=>'Cairo','location_type'=>'GOVERNORATE','parent_id'=>$country['id']]);
    $district = $service->createLocation(['code'=>'district','name_ar'=>'District','name_en'=>'District','location_type'=>'DISTRICT','parent_id'=>$governorate['id']]);
    d1ok($country['code'] === 'EG' && $district['parent_id'] === $governorate['id'], 'Valid hierarchy and normalization');
    d1code(fn() => $service->createLocation(['code'=>'bad1','name_ar'=>'Bad','name_en'=>'Bad','location_type'=>'CITY','parent_id'=>$district['id']]), 'GEOGRAPHY_INVALID_PARENT_LEVEL');
    d1code(fn() => $service->createLocation(['code'=>'bad2','name_ar'=>'Bad','name_en'=>'Bad','location_type'=>'COUNTRY','parent_id'=>$country['id']]), 'GEOGRAPHY_INVALID_PARENT_LEVEL');
    d1code(fn() => $service->createLocation(['code'=>'bad3','name_ar'=>'Bad','name_en'=>'Bad','location_type'=>'CITY']), 'GEOGRAPHY_INVALID_PARENT_LEVEL');
    d1code(fn() => $service->updateLocation($governorate['id'], ['parent_id'=>$governorate['id']]), 'GEOGRAPHY_SELF_PARENT');
    d1code(fn() => $service->updateLocation($governorate['id'], ['parent_id'=>$district['id']]), 'GEOGRAPHY_CYCLE_DETECTED');
    d1code(fn() => $service->deactivateLocation($country['id']), 'LOCATION_HAS_ACTIVE_DESCENDANTS');
    $service->deactivateLocation($district['id']); $service->deactivateLocation($governorate['id']);
    d1code(fn() => $service->reactivateLocation($district['id']), 'PARENT_CATALOG_INACTIVE');
    $service->reactivateLocation($governorate['id']); $service->reactivateLocation($district['id']);
    $giza = $service->createLocation(['code'=>'giza','name_ar'=>'Giza','name_en'=>'Giza','location_type'=>'GOVERNORATE','parent_id'=>$country['id']]);
    $moved = $service->updateLocation($district['id'], ['parent_id'=>$giza['id'], 'name_en'=>'Moved']);
    d1ok($moved['parent_id'] === $giza['id'] && $moved['name_en'] === 'Moved', 'Parent reassignment');
    d1code(fn() => $service->updateLocation($district['id'], ['code'=>'IMMUTABLE']), 'CATALOG_IDENTITY_IMMUTABLE');
    $trace->ids = [];
    $country2 = $service->createLocation(['code'=>'second','name_ar'=>'Second','name_en'=>'Second','location_type'=>'COUNTRY']);
    d1ok($trace->ids === [], 'New Country uses transactional insert and database integrity without a global lock');
    d1locks($trace, fn() => $service->updateLocation($district['id'], ['parent_id'=>$country2['id']]), [$country['id'], $country2['id'], $district['id'], $giza['id']]);
    d1locks($trace, fn() => $service->updateLocation($district['id'], ['parent_id'=>$giza['id']]), [$country['id'], $country2['id'], $district['id'], $giza['id']]);
    d1locks($trace, fn() => $service->deactivateLocation($district['id']), [$country['id'], $district['id'], $giza['id']]);
    d1locks($trace, fn() => $service->reactivateLocation($district['id']), [$country['id'], $district['id'], $giza['id']]);
    d1locks($trace, fn() => $service->createLocation(['code'=>'leaf','name_ar'=>'Leaf','name_en'=>'Leaf','location_type'=>'AREA','parent_id'=>$giza['id']]), [$country['id'], $giza['id']]);
    $leaf = $service->listChildren($giza['id'], ['location_type'=>'AREA'])[0];
    d1locks($trace, fn() => $service->deleteLocation($leaf['id']), [$country['id'], $leaf['id'], $giza['id']]);

    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec("UPDATE geographic_locations SET status = 'inactive' WHERE id = " . $district['id']);
    d1code(fn() => $service->deactivateLocation($district['id']), 'CATALOG_ITEM_INACTIVE');
    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec("UPDATE geographic_locations SET status = 'inactive' WHERE id = " . $giza['id']);
    d1code(fn() => $service->reactivateLocation($district['id']), 'PARENT_CATALOG_INACTIVE');
    $service->reactivateLocation($giza['id']);
    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec("UPDATE geographic_locations SET status = 'active' WHERE id = " . $district['id']);
    d1code(fn() => $service->reactivateLocation($district['id']), 'CATALOG_ITEM_INACTIVE');
    $deepRoot = $service->createLocation(['code'=>'deep-root','name_ar'=>'Root','name_en'=>'Root','location_type'=>'COUNTRY']);
    $deepParent = $service->createLocation(['code'=>'deep-parent','name_ar'=>'Parent','name_en'=>'Parent','location_type'=>'CITY','parent_id'=>$deepRoot['id']]);
    $deepLeaf = $service->createLocation(['code'=>'deep-leaf','name_ar'=>'Leaf','name_en'=>'Leaf','location_type'=>'DISTRICT','parent_id'=>$deepParent['id']]);
    $service->deactivateLocation($deepLeaf['id']); $service->deactivateLocation($deepParent['id']);
    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec("UPDATE geographic_locations SET status = 'active' WHERE id = " . $deepLeaf['id']);
    d1code(fn() => $service->deactivateLocation($deepRoot['id']), 'LOCATION_HAS_ACTIVE_DESCENDANTS');
    d1ok($service->findLocation($deepParent['id'])['status'] === 'inactive', 'No status cascading');
    $emptyParent = $service->createLocation(['code'=>'empty-parent','name_ar'=>'Empty','name_en'=>'Empty','location_type'=>'CITY','parent_id'=>$deepRoot['id']]);
    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec('UPDATE geographic_locations SET parent_id = ' . $emptyParent['id'] . ' WHERE id = ' . $deepLeaf['id']);
    d1code(fn() => $service->deleteLocation($emptyParent['id']), 'CATALOG_ITEM_REFERENCED');
    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec("UPDATE geographic_locations SET provenance = 'SYSTEM_SEED' WHERE id = " . $district['id']);
    d1code(fn() => $service->deleteLocation($district['id']), 'SYSTEM_SEED_DELETE_FORBIDDEN');
    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec("UPDATE geographic_locations SET status = 'inactive' WHERE id = " . $giza['id']);
    d1code(fn() => $service->createLocation(['code'=>'stale','name_ar'=>'Stale','name_en'=>'Stale','location_type'=>'CITY','parent_id'=>$giza['id']]), 'PARENT_CATALOG_INACTIVE');
    $service->reactivateLocation($giza['id']);
    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec("UPDATE geographic_locations SET status = 'inactive' WHERE id = " . $giza['id']);
    d1code(fn() => $service->updateLocation($district['id'], ['parent_id'=>$giza['id']]), 'PARENT_CATALOG_INACTIVE');
    $service->reactivateLocation($giza['id']);
    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec('UPDATE geographic_locations SET parent_id = ' . $country2['id'] . ' WHERE id = ' . $giza['id']);
    d1locks($trace, fn() => $service->updateLocation($district['id'], ['name_en'=>'Current hierarchy']), [$country['id'], $district['id'], $giza['id'], $country2['id'], $district['id'], $giza['id']]);
    $wideRoot = $service->createLocation(['code'=>'wide-root','name_ar'=>'Wide','name_en'=>'Wide','location_type'=>'COUNTRY']);
    for ($index = 0; $index < 51; $index++) {
        $wideChild = $service->createLocation(['code'=>'wide-' . $index,'name_ar'=>'Child','name_en'=>'Child','location_type'=>'CITY','parent_id'=>$wideRoot['id']]);
        if ($index < 50) { $service->deactivateLocation($wideChild['id']); }
    }
    d1code(fn() => $service->deactivateLocation($wideRoot['id']), 'LOCATION_HAS_ACTIVE_DESCENDANTS');
    d1ok($service->findLocation($wideRoot['id'])['status'] === 'active', 'Active descendant beyond first page blocks deactivation');
    $service->deactivateLocation($wideChild['id']);
    $service->deactivateLocation($wideRoot['id']);
    echo "D1 GeographicLocationService focused acceptance: PASS\n";
} finally {
    if ($database !== null && $database->connection()->inTransaction()) $database->rollback();
    if ($made) $server->exec("DROP DATABASE $name");
}
