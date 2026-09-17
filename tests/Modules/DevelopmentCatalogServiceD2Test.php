<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Exceptions\ValidationException;
use App\Modules\Property\Services\DevelopmentCatalogService;
use App\Modules\Property\Services\GeographicLocationService;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function d2ok(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
function d2code(callable $call, string $code): void
{
    try { $call(); }
    catch (ValidationException $exception) { d2ok(isset($exception->errors()[$code]), 'Expected ' . $code); return; }
    throw new RuntimeException('Expected ' . $code);
}
function d2data(string $code, array $extra = []): array { return $extra + ['code'=>$code, 'name_ar'=>$code, 'name_en'=>$code]; }

// Same single-process PDO tracing and discovery-window hook used by D1.
final class D2Trace
{
    public array $locks = [];
    public array $transactions = [];
    public $beforeTransaction = null;
}
final class D2Statement extends PDOStatement
{
    private array $values = [];
    protected function __construct(private D2Trace $trace) {}
    public function bindValue($param, $value, $type = PDO::PARAM_STR): bool
    {
        $this->values[$param] = $value;
        return parent::bindValue($param, $value, $type);
    }
    public function execute(?array $params = null): bool
    {
        if (str_contains($this->queryString, 'FOR UPDATE')) {
            d2ok(preg_match('/FROM `([a-z_]+)`/', $this->queryString, $match) === 1, 'Trace table');
            $this->trace->locks[] = [$match[1], (int) reset($this->values)];
        }
        return parent::execute($params);
    }
}
final class D2Database implements DatabaseConnectionInterface
{
    public function __construct(private DatabaseManager $inner, private D2Trace $trace) {}
    public function connection(): PDO { return $this->inner->connection(); }
    public function beginTransaction(): void { $this->inner->beginTransaction(); }
    public function commit(): void { $this->inner->commit(); }
    public function rollback(): void { $this->inner->rollback(); }
    public function transaction(callable $callback): mixed
    {
        $hook = $this->trace->beforeTransaction;
        $this->trace->beforeTransaction = null;
        if ($hook !== null) { $hook($this->connection()); }
        $this->trace->transactions[] = 'begin';
        try {
            $result = $this->inner->transaction($callback);
            $this->trace->transactions[] = 'commit';
            return $result;
        } catch (Throwable $exception) {
            $this->trace->transactions[] = 'rollback';
            throw $exception;
        }
    }
}
function d2locks(D2Trace $trace, callable $call, array $expected): mixed
{
    $trace->locks = [];
    $result = $call();
    d2ok($trace->locks === $expected, 'Lock order: ' . json_encode($trace->locks) . ' expected ' . json_encode($expected));
    return $result;
}

$root = dirname(__DIR__, 2); Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php'; $connection = $config['connections']['mysql'];
$name = 'directors_resale_platform_d2_test_' . getmypid();
d2ok(preg_match('/^directors_resale_platform_d2_test_[0-9]+$/D', $name) === 1 && $name !== $connection['database'], 'Unsafe database');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $connection['host'], $connection['port']), $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$made = false; $database = null;
try {
    $server->exec("CREATE DATABASE $name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $made = true;
    $config['connections']['mysql']['database'] = $name; $database = new DatabaseManager($config);
    $files = glob($root . '/database/migrations/*.sql') ?: []; sort($files);
    foreach ($files as $file) { $database->connection()->exec(file_get_contents($file)); }
    $trace = new D2Trace();
    $database->connection()->setAttribute(PDO::ATTR_STATEMENT_CLASS, [D2Statement::class, [$trace]]);
    $container = new Container(); (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, new D2Database($database, $trace));
    $service = $container->make(DevelopmentCatalogService::class);
    $geography = $container->make(GeographicLocationService::class);

    $developer = $service->createDeveloper(d2data(' first ', ['status'=>'inactive', 'created_by_user_id'=>999999]));
    d2ok($developer['code'] === 'FIRST' && $developer['status'] === 'active' && $developer['created_by_user_id'] === null && strlen($developer['ulid']) === 26, 'Canonical Developer creation');
    d2ok($trace->transactions === ['begin', 'commit'], 'Developer create owns transaction');
    $edited = $service->updateDeveloper($developer['id'], ['name_en'=>'Edited', 'sort_order'=>7]);
    d2ok($edited['name_en'] === 'Edited' && $edited['sort_order'] === 7 && $edited['code'] === 'FIRST', 'Developer presentation update');
    foreach (['id', 'ulid', 'code', 'provenance', 'status'] as $field) {
        d2code(fn() => $service->updateDeveloper($developer['id'], [$field=>$developer[$field]]), 'CATALOG_IDENTITY_IMMUTABLE');
    }
    d2code(fn() => $service->createDeveloper(d2data('first')), 'CATALOG_CODE_ALREADY_EXISTS');
    d2code(fn() => $service->createDeveloper(d2data('   ')), 'CATALOG_CODE_ALREADY_EXISTS');
    $other = $service->createDeveloper(d2data('other'));
    $inactive = $service->createDeveloper(d2data('inactive'));
    $service->deactivateDeveloper($inactive['id']);

    $standalone = $service->createProject(d2data(' standalone '));
    d2ok($standalone['developer_id'] === null && $standalone['geographic_location_id'] === null && $standalone['code'] === 'STANDALONE', 'Optional Project references');
    $project = d2locks($trace, fn() => $service->createProject(d2data(' project ', ['developer_id'=>$developer['id']])), [['developers', $developer['id']]]);
    d2code(fn() => $service->createProject(d2data('bad-developer', ['developer_id'=>$inactive['id']])), 'PARENT_CATALOG_INACTIVE');
    d2code(fn() => $service->createProject(d2data('missing-developer', ['developer_id'=>999999])), 'PARENT_CATALOG_INACTIVE');
    d2code(fn() => $service->createProject(d2data('project')), 'CATALOG_CODE_ALREADY_EXISTS');
    foreach (['id', 'ulid', 'code', 'provenance', 'status'] as $field) {
        d2code(fn() => $service->updateProject($project['id'], [$field=>$project[$field]]), 'CATALOG_IDENTITY_IMMUTABLE');
    }
    d2locks($trace, fn() => d2code(fn() => $service->deactivateDeveloper($developer['id']), 'DEVELOPER_HAS_ACTIVE_PROJECTS'), [['developers', $developer['id']], ['projects', $developer['id']]]);
    d2ok($service->findDeveloper($developer['id'])['status'] === 'active' && $service->findProject($project['id'])['status'] === 'active', 'Blocked Developer transition does not cascade');
    d2code(fn() => $service->updateProject($project['id'], ['developer_id'=>$inactive['id'], 'name_en'=>'Rejected']), 'PARENT_CATALOG_INACTIVE');
    d2ok($service->findProject($project['id'])['developer_id'] === $developer['id'] && $service->findProject($project['id'])['name_en'] === ' project ', 'Rejected assignment is atomic');
    d2locks($trace, fn() => $service->updateProject($project['id'], ['developer_id'=>$other['id']]), [['developers', $developer['id']], ['developers', $other['id']], ['projects', $project['id']]]);
    d2locks($trace, fn() => $service->updateProject($project['id'], ['developer_id'=>$developer['id']]), [['developers', $developer['id']], ['developers', $other['id']], ['projects', $project['id']]]);
    $removed = $service->updateProject($project['id'], ['developer_id'=>null]);
    d2ok($removed['developer_id'] === null, 'Developer removal');
    $service->updateProject($project['id'], ['developer_id'=>$developer['id']]);
    $service->deactivateProject($project['id']);
    $service->deactivateDeveloper($developer['id']);
    d2ok($service->findProject($project['id'])['status'] === 'inactive', 'Developer deactivation preserves inactive Project');
    d2code(fn() => $service->deleteDeveloper($developer['id']), 'CATALOG_ITEM_REFERENCED');
    d2code(fn() => $service->reactivateProject($project['id']), 'PARENT_CATALOG_INACTIVE');
    $service->reactivateDeveloper($developer['id']);
    d2ok($service->findProject($project['id'])['status'] === 'inactive', 'Developer reactivation does not cascade');
    $service->reactivateProject($project['id']);
    $service->deactivateProject($standalone['id']); $service->reactivateProject($standalone['id']);

    $location = $geography->createLocation(d2data('geo', ['location_type'=>'COUNTRY']));
    $location2 = $geography->createLocation(d2data('geo2', ['location_type'=>'COUNTRY']));
    $linked = d2locks($trace, fn() => $service->createProject(d2data('linked', ['developer_id'=>$other['id'], 'geographic_location_id'=>$location['id']])), [['developers', $other['id']], ['geographic_locations', $location['id']]]);
    d2locks($trace, fn() => $service->updateProject($project['id'], ['geographic_location_id'=>$location['id']]), [['developers', $developer['id']], ['geographic_locations', $location['id']], ['projects', $project['id']]]);
    $geography->deactivateLocation($location['id']);
    d2ok($service->findProject($linked['id'])['status'] === 'active', 'Geography deactivation does not cascade');
    d2code(fn() => $service->createProject(d2data('bad-geo', ['geographic_location_id'=>$location['id']])), 'PARENT_CATALOG_INACTIVE');
    d2code(fn() => $service->updateProject($standalone['id'], ['geographic_location_id'=>$location['id']]), 'PARENT_CATALOG_INACTIVE');
    d2code(fn() => $service->updateProject($standalone['id'], ['geographic_location_id'=>999999]), 'PARENT_CATALOG_INACTIVE');
    $service->deactivateProject($linked['id']);
    d2locks($trace, fn() => $service->reactivateProject($linked['id']), [['developers', $other['id']], ['projects', $linked['id']]]);
    d2ok($service->findProject($linked['id'])['geographic_location_id'] === $location['id'], 'Inactive Geography does not prevent reactivation');
    $service->updateProject($linked['id'], ['geographic_location_id'=>$location['id'], 'name_en'=>'Historical link']);
    $service->updateProject($linked['id'], ['geographic_location_id'=>$location2['id']]);
    d2ok($service->findProject($linked['id'])['geographic_location_id'] === $location2['id'], 'Geography reassignment');
    $service->updateProject($linked['id'], ['geographic_location_id'=>null]);
    d2ok($service->findProject($linked['id'])['geographic_location_id'] === null, 'Geography removal');

    $phase = d2locks($trace, fn() => $service->createProjectPhase($project['id'], d2data(' phase ')), [['projects', $project['id']]]);
    d2ok($phase['code'] === 'PHASE' && $phase['project_id'] === $project['id'], 'Phase create');
    $phaseEdited = $service->updateProjectPhase($project['id'], $phase['id'], ['name_en'=>'Phase edited', 'sort_order'=>9]);
    d2ok($phaseEdited['name_en'] === 'Phase edited' && $phaseEdited['sort_order'] === 9, 'Phase presentation update');
    foreach (['id', 'ulid', 'code', 'provenance', 'status', 'project_id'] as $field) {
        d2code(fn() => $service->updateProjectPhase($project['id'], $phase['id'], [$field=>$phase[$field]]), 'CATALOG_IDENTITY_IMMUTABLE');
    }
    d2code(fn() => $service->createProjectPhase($project['id'], d2data('phase')), 'CATALOG_CODE_ALREADY_EXISTS');
    $sameCode = $service->createProjectPhase($standalone['id'], d2data('phase'));
    d2ok($sameCode['code'] === $phase['code'], 'Phase code uniqueness is scoped to Project');
    d2code(fn() => $service->createProjectPhase(999999, d2data('missing-parent')), 'PARENT_CATALOG_INACTIVE');
    d2locks($trace, fn() => d2code(fn() => $service->deactivateProject($project['id']), 'PROJECT_HAS_ACTIVE_PHASES'), [['developers', $developer['id']], ['projects', $project['id']], ['project_phases', $project['id']]]);
    d2ok($service->findProject($project['id'])['status'] === 'active' && $service->findProjectPhase($phase['id'])['status'] === 'active', 'Blocked Project transition does not cascade');
    d2locks($trace, fn() => $service->deactivateProjectPhase($project['id'], $phase['id']), [['projects', $project['id']], ['project_phases', $phase['id']]]);
    d2ok($service->findProject($project['id'])['status'] === 'active', 'Phase lifecycle preserves Project');
    $service->deactivateProject($project['id']);
    d2code(fn() => $service->createProjectPhase($project['id'], d2data('inactive-parent')), 'PARENT_CATALOG_INACTIVE');
    d2locks($trace, fn() => d2code(fn() => $service->reactivateProjectPhase($project['id'], $phase['id']), 'PARENT_CATALOG_INACTIVE'), [['projects', $project['id']], ['project_phases', $phase['id']]]);
    d2code(fn() => $service->deleteProject($project['id']), 'CATALOG_ITEM_REFERENCED');
    $service->reactivateProject($project['id']);
    d2ok($service->findProjectPhase($phase['id'])['status'] === 'inactive', 'Project reactivation does not cascade');
    d2locks($trace, fn() => $service->reactivateProjectPhase($project['id'], $phase['id']), [['projects', $project['id']], ['project_phases', $phase['id']]]);
    d2ok($service->updateProjectPhase($standalone['id'], $phase['id'], ['name_en'=>'Wrong scope']) === null, 'Phase update is scoped');
    d2code(fn() => $service->deactivateProjectPhase($standalone['id'], $phase['id']), 'CATALOG_ITEM_INACTIVE');
    d2code(fn() => $service->deleteProjectPhase($standalone['id'], $phase['id']), 'CATALOG_ITEM_INACTIVE');

    $seedDeveloper = $service->createDeveloper(d2data('seed-developer', ['provenance'=>'SYSTEM_SEED']));
    $seedProject = $service->createProject(d2data('seed-project', ['provenance'=>'SYSTEM_SEED']));
    $seedPhase = $service->createProjectPhase($seedProject['id'], d2data('seed-phase', ['provenance'=>'SYSTEM_SEED']));
    d2code(fn() => $service->deleteDeveloper($seedDeveloper['id']), 'SYSTEM_SEED_DELETE_FORBIDDEN');
    d2code(fn() => $service->deleteProject($seedProject['id']), 'SYSTEM_SEED_DELETE_FORBIDDEN');
    d2code(fn() => $service->deleteProjectPhase($seedProject['id'], $seedPhase['id']), 'SYSTEM_SEED_DELETE_FORBIDDEN');
    $service->updateDeveloper($seedDeveloper['id'], ['name_en'=>'Seed editable']);
    $service->deactivateDeveloper($seedDeveloper['id']); $service->reactivateDeveloper($seedDeveloper['id']);
    $free = $service->createDeveloper(d2data('free'));
    d2ok($service->deleteDeveloper($free['id']) && $service->findDeveloper($free['id']) === null, 'Unreferenced admin Developer deletion');
    d2locks($trace, fn() => $service->deleteProjectPhase($standalone['id'], $sameCode['id']), [['projects', $standalone['id']], ['project_phases', $sameCode['id']]]);
    d2ok($service->deleteProject($standalone['id']) && $service->findProject($standalone['id']) === null, 'Unreferenced admin Project deletion');
    d2ok($service->findProjectPhase($sameCode['id']) === null, 'Unreferenced admin Phase deletion');

    // Deterministic changes after discovery prove the locked state is authoritative.
    $retryProject = $service->createProject(d2data('retry', ['developer_id'=>$other['id']]));
    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec('UPDATE projects SET developer_id = ' . $inactive['id'] . ' WHERE id = ' . $retryProject['id']);
    $trace->transactions = [];
    d2locks($trace, fn() => $service->updateProject($retryProject['id'], ['name_en'=>'Fresh']), [['developers', $other['id']], ['projects', $retryProject['id']], ['developers', $inactive['id']], ['projects', $retryProject['id']]]);
    d2ok($trace->transactions === ['begin', 'rollback', 'begin', 'commit'], 'Changed Developer causes rollback before rediscovery');
    d2ok($service->findProject($retryProject['id'])['developer_id'] === $inactive['id'], 'Presentation update retains current relationship');
    $service->deactivateProject($retryProject['id']);
    d2code(fn() => $service->reactivateProject($retryProject['id']), 'PARENT_CATALOG_INACTIVE');
    $service->updateProject($retryProject['id'], ['developer_id'=>null]); $service->reactivateProject($retryProject['id']);
    d2ok($service->findProject($retryProject['id'])['status'] === 'active', 'Removal of inactive Developer permits reactivation');

    $freshDeveloper = $service->createDeveloper(d2data('fresh-developer'));
    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec("UPDATE developers SET status = 'inactive' WHERE id = " . $freshDeveloper['id']);
    d2code(fn() => $service->updateProject($retryProject['id'], ['developer_id'=>$freshDeveloper['id']]), 'PARENT_CATALOG_INACTIVE');
    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec("UPDATE geographic_locations SET status = 'inactive' WHERE id = " . $location2['id']);
    d2code(fn() => $service->updateProject($retryProject['id'], ['geographic_location_id'=>$location2['id']]), 'PARENT_CATALOG_INACTIVE');
    $service->deactivateProjectPhase($project['id'], $phase['id']);
    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec("UPDATE projects SET status = 'inactive' WHERE id = " . $project['id']);
    d2code(fn() => $service->reactivateProjectPhase($project['id'], $phase['id']), 'PARENT_CATALOG_INACTIVE');
    $trace->beforeTransaction = fn(PDO $pdo) => $pdo->exec("UPDATE developers SET provenance = 'SYSTEM_SEED' WHERE id = " . $freshDeveloper['id']);
    d2code(fn() => $service->deleteDeveloper($freshDeveloper['id']), 'SYSTEM_SEED_DELETE_FORBIDDEN');
    d2ok(!$database->connection()->inTransaction(), 'All transactions completed');
    echo "D2 DevelopmentCatalogService focused acceptance: PASS\n";
} finally {
    if ($database !== null && $database->connection()->inTransaction()) { $database->rollback(); }
    if ($made) { $server->exec("DROP DATABASE $name"); }
}
