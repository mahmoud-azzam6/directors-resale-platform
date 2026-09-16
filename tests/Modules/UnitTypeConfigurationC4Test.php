<?php
declare(strict_types=1);

use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\UnitTypeConfigurationRepository;
use App\Modules\Property\Repositories\PropertyCatalogRepository;
use App\Modules\Property\Repositories\MeasurementDefinitionRepository;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Modules\Property\Services\UnitTypeConfigurationService;
use App\Providers\AppServiceProvider;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function c4check(bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
}
function c4error(callable $call, string $code): void {
    try { $call(); } catch (ValidationException $e) {
        c4check(isset($e->errors()[$code]), 'Wrong error: ' . json_encode($e->errors()));
        return;
    }
    throw new RuntimeException('Expected ' . $code);
}

$root = dirname(__DIR__, 2);
Dotenv\Dotenv::createImmutable($root)->safeLoad();
$config = require $root . '/config/database.php';
$c = $config['connections']['mysql'];
$name = 'directors_resale_platform_c4_test_' . getmypid();
c4check(preg_match('/^directors_resale_platform_c4_test_[0-9]+$/D', $name) === 1 && $name !== $c['database'], 'Unsafe database');
$server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $c['host'], $c['port']), $c['username'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
$db = null;
try {
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $config['connections']['mysql']['database'] = $name;
    $db = new DatabaseManager($config);
    $pdo = $db->connection();
    $files = glob($root . '/database/migrations/*.sql');
    sort($files);
    foreach ($files as $file) { $pdo->exec(file_get_contents($file)); }
    $container = new Container();
    (new AppServiceProvider($container, []))->register();
    $container->instance(DatabaseConnectionInterface::class, $db);
    $s = $container->make(UnitTypeConfigurationService::class);
    $catalog = $container->make(PropertyCatalogService::class);
    $repo = $container->make(UnitTypeConfigurationRepository::class);
    $units = $container->make(PropertyCatalogRepository::class);
    $measurements = $container->make(MeasurementDefinitionRepository::class);
    $category = $catalog->createCategory(['code'=>'C4','name_ar'=>'C4','name_en'=>'C4']);
    $unit = $catalog->createUnitType(['code'=>'C4','name_ar'=>'C4','name_en'=>'C4','property_category_id'=>$category['id']]);
    $u = $unit['id'];
    $m = $catalog->createMeasurementDefinition(['code'=>'C4','name_ar'=>'C4','name_en'=>'C4','default_unit_code'=>'SQM']);
    $a = $catalog->createAttributeDefinition(['code'=>'C4','name_ar'=>'C4','name_en'=>'C4','data_type'=>'ENUM']);
    $o = $catalog->createAttributeOption($a['id'], ['code'=>'C4','name_ar'=>'C4','name_en'=>'C4']);
    $first = $s->createBlankDraft($u);
    $s->activate($u, $first['id']);
    $draft = $s->createBlankDraft($u);
    $id = $draft['id'];
    $mr = $s->addMeasurementRule($u, $id, ['measurement_definition_id'=>$m['id'],'requirement'=>'REQUIRED','is_primary'=>1]);
    $s->addAttributeRule($u, $id, ['attribute_definition_id'=>$a['id'],'requirement'=>'OPTIONAL']);
    $catalog->deactivateMeasurementDefinition($m['id']);
    c4error(fn() => $s->updateMeasurementRule($u,$id,$mr['id'],['sort_order'=>2]), 'MEASUREMENT_DEFINITION_INACTIVE');
    c4error(fn() => $s->activate($u,$id), 'MEASUREMENT_DEFINITION_INACTIVE');
    $catalog->reactivateMeasurementDefinition($m['id']);
    c4error(fn() => $s->updateMeasurementRule($u,$id,$mr['id'],['requirement'=>null]), 'PRIMARY_MEASUREMENT_MUST_BE_REQUIRED');
    $catalog->deactivateAttributeOption($a['id'],$o['id']);
    c4error(fn() => $s->activate($u,$id), 'ENUM_HAS_NO_ACTIVE_OPTIONS');
    c4check($s->findActive($u)['id']===$first['id'] && $s->findDraft($u)['id']===$id, 'Failed validation changed lifecycle');
    $catalog->reactivateAttributeOption($a['id'],$o['id']);
    $before = $s->aggregate($id);
    // Force failure after the previous ACTIVE has been historicalized.
    $pdo->exec("CREATE TRIGGER c4_fail_activation BEFORE UPDATE ON unit_type_configuration_versions FOR EACH ROW BEGIN IF NEW.id = $id AND NEW.status = 'active' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'c4 injected failure'; END IF; END");
    try {
        try { $s->activate($u,$id); throw new RuntimeException('Expected SQL failure'); }
        catch (PDOException $e) { c4check($e->getCode()==='45000', 'Unexpected SQL error'); }
    } finally { $pdo->exec('DROP TRIGGER c4_fail_activation'); }
    c4check($s->findActive($u)['id']===$first['id'] && $s->aggregate($id)===$before, 'Activation rollback failed');
    $s->activate($u,$id);
    c4check($s->findConfiguration($first['id'])['status']==='historical' && $s->findActive($u)['id']===$id && $s->findDraft($u)===null, 'Activation transition');
    c4check($repo->listMeasurementRules($id)[0]===$mr, 'Activation changed rule');
    foreach ([$first['id'],$id] as $immutable) { c4error(fn()=>$s->deleteDraft($u,$immutable),'CONFIGURATION_NOT_DRAFT'); }
    $clone = $s->cloneActiveDraft($u);
    $counter = $units->findUnitTypeById($u)['last_allocated_configuration_version'];
    $s->deleteDraft($u,$clone['id']);
    c4check($repo->findConfigurationById($clone['id'])===null && $repo->listMeasurementRules($clone['id'])===[] && $repo->listAttributeRules($clone['id'])===[], 'Draft deletion');
    c4check($units->findUnitTypeById($u)['last_allocated_configuration_version']===$counter, 'Counter decremented');
    c4check($s->createBlankDraft($u)['version_number']===$counter+1, 'Deleted version reused');
    echo "C4 focused MariaDB deletion / activation / rollback / corrections: PASS\n";
} finally {
    if ($db !== null && $db->connection()->inTransaction()) { $db->rollback(); }
    if ($created) { $server->exec("DROP DATABASE `$name`"); }
}
