<?php
declare(strict_types=1);
use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\DatabaseManager;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\PropertyCatalogRepository;
use App\Modules\Property\Repositories\UnitTypeConfigurationRepository;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Modules\Property\Services\UnitTypeConfigurationService;
use App\Providers\AppServiceProvider;
use Dotenv\Dotenv;
require dirname(__DIR__, 2) . '/vendor/autoload.php';
function c5cAssert(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
function c5cWorker(string $root,string $dbName,string $mode,int $unit,int $configId,int $measurement):array{
 $config=require $root.'/config/database.php';$config['connections']['mysql']['database']=$dbName;$db=new DatabaseManager($config);$db->connection()->exec('SET SESSION innodb_lock_wait_timeout = 5');
 $c=new Container();(new AppServiceProvider($c,[]))->register();$c->instance(DatabaseConnectionInterface::class,$db);$s=$c->make(UnitTypeConfigurationService::class);
 try{if($mode==='delete')$s->deleteDraft($unit,$configId);else $s->addMeasurementRule($unit,$configId,['measurement_definition_id'=>$measurement,'requirement'=>'REQUIRED','is_primary'=>false]);return ['result'=>'ok'];}
 catch(ValidationException $e){return ['result'=>'validation','code'=>(string)array_key_first($e->errors())];}
 catch(Throwable $e){return ['result'=>'error','error'=>$e::class.': '.$e->getMessage()];}
}
/** @param array<int,resource> $pipes @return resource */
function c5cStart(string $db,string $mode,int $unit,int $configuration,int $measurement,array &$pipes):mixed{
 $cmd=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' --c5c-worker '.escapeshellarg($db).' '.escapeshellarg($mode)." $unit $configuration $measurement";
 $p=proc_open($cmd,[1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));if(!is_resource($p))throw new RuntimeException('Worker launch failed.');stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);return $p;
}
/** @param resource $process @param array<int,resource> $pipes */
function c5cFinish(mixed $process,array $pipes):array{
 $deadline=microtime(true)+6;while(proc_get_status($process)['running']){if(microtime(true)>=$deadline){proc_terminate($process);throw new RuntimeException('Worker timeout.');}usleep(20_000);}
 $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);$result=json_decode(trim($out),true);if($exit!==0||!is_array($result))throw new RuntimeException('Worker failure: '.trim($err.' '.$out));return $result;
}
$root=dirname(__DIR__,2);Dotenv::createImmutable($root)->safeLoad();
if(($argv[1]??null)==='--c5c-worker'){$r=c5cWorker($root,(string)$argv[2],(string)$argv[3],(int)$argv[4],(int)$argv[5],(int)$argv[6]);echo json_encode($r,JSON_THROW_ON_ERROR);exit($r['result']==='error'?1:0);}
$config=require $root.'/config/database.php';$connection=$config['connections']['mysql'];$name='directors_resale_platform_c5c_test_'.getmypid();
c5cAssert(preg_match('/^directors_resale_platform_c5c_test_[0-9]+$/D',$name)===1&&$name!==$connection['database'],'Unsafe database');
$server=new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4',$connection['host'],$connection['port']),$connection['username'],$connection['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$created=false;$db=null;$a=$b=null;$ap=$bp=[];
try{
 $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$created=true;$config['connections']['mysql']['database']=$name;$db=new DatabaseManager($config);$pdo=$db->connection();
 $files=glob($root.'/database/migrations/*.sql')?:[];sort($files);foreach($files as $f)$pdo->exec(file_get_contents($f));
 $c=new Container();(new AppServiceProvider($c,[]))->register();$c->instance(DatabaseConnectionInterface::class,$db);$catalog=$c->make(PropertyCatalogService::class);$service=$c->make(UnitTypeConfigurationService::class);$units=$c->make(PropertyCatalogRepository::class);$configs=$c->make(UnitTypeConfigurationRepository::class);
 $category=$catalog->createCategory(['code'=>'C5C','name_ar'=>'C5C','name_en'=>'C5C']);$unit=$catalog->createUnitType(['property_category_id'=>$category['id'],'code'=>'C5C','name_ar'=>'C5C','name_en'=>'C5C']);$measurement=$catalog->createMeasurementDefinition(['code'=>'C5C','name_ar'=>'C5C','name_en'=>'C5C','default_unit_code'=>'SQM']);$draft=$service->createBlankDraft($unit['id']);$before=$units->findUnitTypeById($unit['id'])['last_allocated_configuration_version'];
 $lock='bf014_c5c_delete_'.getmypid();$pdo->exec("CREATE TRIGGER c5c_pause BEFORE DELETE ON unit_type_configuration_versions FOR EACH ROW BEGIN IF OLD.id = {$draft['id']} THEN DO GET_LOCK('$lock',0);DO SLEEP(2);END IF;END");
 $a=c5cStart($name,'delete',$unit['id'],$draft['id'],$measurement['id'],$ap);$deadline=microtime(true)+3;$owner=null;do{$st=$server->prepare('SELECT IS_USED_LOCK(?)');$st->execute([$lock]);$owner=$st->fetchColumn();if($owner!==null)break;usleep(20_000);}while(microtime(true)<$deadline);c5cAssert($owner!==null,'Delete did not reach guarded configuration deletion');
 $startedB=microtime(true);$b=c5cStart($name,'mutate',$unit['id'],$draft['id'],$measurement['id'],$bp);$ra=c5cFinish($a,$ap);$a=null;$rb=c5cFinish($b,$bp);$b=null;$wait=microtime(true)-$startedB;
 c5cAssert($ra['result']==='ok','Delete result');c5cAssert($rb['result']==='validation'&&($rb['code']??null)==='CONFIGURATION_STRUCTURE_IMMUTABLE','Mutation result');c5cAssert($wait>=.5,'Mutation did not contend');
 c5cAssert($configs->findConfigurationById($draft['id'])===null,'Configuration survived deletion');c5cAssert($configs->listMeasurementRules($draft['id'])===[],'Orphan measurement rules');c5cAssert($configs->listAttributeRules($draft['id'])===[],'Orphan attribute rules');c5cAssert($configs->findDraftConfigurationForUnitType($unit['id'])===null,'Draft remains');c5cAssert($units->findUnitTypeById($unit['id'])['last_allocated_configuration_version']===$before,'Counter changed');c5cAssert($configs->findConfigurationByVersion($unit['id'],1)===null&&$service->createBlankDraft($unit['id'])['version_number']===2,'Deleted version was reused');
 echo "C5c real deletion vs mutation: PASS; B_wait_seconds=".number_format($wait,3)."\n";
}finally{foreach([[$a,$ap],[$b,$bp]]as[$p,$pipes])if($p!==null){proc_terminate($p);foreach($pipes as $pipe)fclose($pipe);proc_close($p);}if($db!==null&&$db->connection()->inTransaction())$db->rollback();if($created)$server->exec("DROP DATABASE `$name`");}
