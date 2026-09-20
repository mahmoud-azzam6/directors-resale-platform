<?php
declare(strict_types=1);
namespace App\Modules\Property\Seeds;
use App\Modules\Property\Services\PropertyCatalogSeedPackage;use App\Modules\Property\Services\PropertyCatalogService;
final class PropertyCategoriesSeedPackage {
 public static function make(PropertyCatalogService $catalog):PropertyCatalogSeedPackage {$rows=[['RESIDENTIAL','Residential'],['COMMERCIAL','Commercial'],['ADMINISTRATIVE','Administrative'],['MEDICAL','Medical'],['LAND','Land'],['OTHER','Other']];return new PropertyCatalogSeedPackage('001-property-categories',1,json_encode($rows,JSON_THROW_ON_ERROR),function()use($catalog,$rows):void{foreach($rows as[$code,$name]){$found=array_values(array_filter($catalog->listCategories(),fn($r)=>$r['code']===$code));if($found!==[]){$r=$found[0];if($r['provenance']!=='SYSTEM_SEED'||$r['name_en']!==$name||$r['status']!=='active')throw new \RuntimeException('SEED_PACKAGE_COLLISION');continue;}$catalog->createCategory(['code'=>$code,'name_ar'=>$name,'name_en'=>$name,'provenance'=>'SYSTEM_SEED']);}});}
}
