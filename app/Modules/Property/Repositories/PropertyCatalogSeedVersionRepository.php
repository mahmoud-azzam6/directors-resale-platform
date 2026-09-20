<?php
declare(strict_types=1);
namespace App\Modules\Property\Repositories;
use App\Core\Database\QueryBuilderInterface;
final class PropertyCatalogSeedVersionRepository {
 public function __construct(private QueryBuilderInterface $query){}
 public function findBySeedKey(string $key):?array{return $this->query->table('property_catalog_seed_versions')->where('seed_key','=',$key)->first();}
 public function recordSuccessfulApplication(string $key,string $checksum,?int $actor=null):array{$id=$this->query->table('property_catalog_seed_versions')->insert(['seed_key'=>$key,'checksum'=>$checksum,'applied_by_user_id'=>$actor]);return $this->findBySeedKey($key)??throw new \RuntimeException('Seed ledger record missing.');}
}
