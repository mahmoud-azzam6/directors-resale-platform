<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit(1); }
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\Container;use App\Modules\Property\Services\PropertyCatalogSeedRunner;use App\Modules\Property\Services\PropertyCatalogService;use App\Modules\Property\Seeds\PropertyCategoriesSeedPackage;use App\Modules\Property\Seeds\UnitTypesSeedPackage;use App\Providers\AppServiceProvider;use Dotenv\Dotenv;
$root=dirname(__DIR__);Dotenv::createImmutable($root)->safeLoad();$config=['app'=>require $root.'/config/app.php','database'=>require $root.'/config/database.php'];
try{$box=new Container();(new AppServiceProvider($box,$config))->register();$catalog=$box->make(PropertyCatalogService::class);$packages=[PropertyCategoriesSeedPackage::make($catalog),UnitTypesSeedPackage::make($catalog)];foreach($box->make(PropertyCatalogSeedRunner::class)->run($packages)as$line)echo "$line\n";exit(0);}catch(Throwable $e){fwrite(STDERR,"FAIL seed runner: ".$e->getMessage()."\n");exit(1);}
