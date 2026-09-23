<?php
declare(strict_types=1);
namespace App\Modules\Property\Repositories;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\Database\QueryBuilderInterface;
use InvalidArgumentException;
use RuntimeException;
final class OrganizationPropertyProfileRepository {
 private const C=['id','organization_property_id','property_category_id','unit_type_id','accepted_configuration_version_id','geographic_location_id','development_reference_type','developer_id','project_id','project_phase_id','revision','created_by_user_id','updated_by_user_id','created_at','updated_at'];
 private const W=['property_category_id','unit_type_id','accepted_configuration_version_id','geographic_location_id','development_reference_type','developer_id','project_id','project_phase_id','updated_by_user_id'];
 public function __construct(private QueryBuilderInterface $q,private DatabaseConnectionInterface $db){}
 public function findByOrganizationPropertyId(int|string $id):?array{$r=$this->q->table('organization_property_profiles')->select(self::C)->where('organization_property_id','=',$this->id($id))->first();return$r===null?null:$this->map($r);}
 public function findByOrganizationPropertyIdForUpdate(int|string $id):?array{$r=$this->q->table('organization_property_profiles')->select(self::C)->where('organization_property_id','=',$this->id($id))->forUpdate()->first();return$r===null?null:$this->map($r);}
 public function create(array $a):array{$allowed=['organization_property_id','property_category_id','unit_type_id','accepted_configuration_version_id','geographic_location_id','development_reference_type','developer_id','project_id','project_phase_id','created_by_user_id','updated_by_user_id'];foreach(array_keys($a)as$k)if(!in_array($k,$allowed,true))throw new InvalidArgumentException('Unknown profile field.');$id=$this->q->table('organization_property_profiles')->insert($a);$r=$this->q->table('organization_property_profiles')->select(self::C)->where('id','=',$id)->first();if($r===null)throw new RuntimeException('Profile missing.');return$this->map($r);}
 public function updateWithExpectedRevision(int|string $property,int|string $revision,array $a):?array{$property=$this->id($property);$revision=$this->id($revision);foreach(array_keys($a)as$k)if(!in_array($k,self::W,true))throw new InvalidArgumentException('Unknown or immutable profile field.');if($a===[])throw new InvalidArgumentException('Profile changes required.');$sets=[];$p=[];foreach($a as$k=>$v){$sets[]="$k=:$k";$p[":$k"]=$v;}$p[':p']=$property;$p[':r']=$revision;$s=$this->db->connection()->prepare('UPDATE organization_property_profiles SET '.implode(',',$sets).',revision=revision+1 WHERE organization_property_id=:p AND revision=:r');if($s===false)throw new RuntimeException('Prepare failed.');$s->execute($p);return$s->rowCount()===1?$this->findByOrganizationPropertyId($property):null;}
 private function id(int|string $v):int{if((!is_int($v)&&(!is_string($v)||!ctype_digit($v)))||(int)$v<1)throw new InvalidArgumentException('Invalid identifier.');return(int)$v;}
 private function map(array $r):array{foreach(['id','organization_property_id','property_category_id','unit_type_id','accepted_configuration_version_id','geographic_location_id','developer_id','project_id','project_phase_id','revision','created_by_user_id','updated_by_user_id']as$k)$r[$k]=$r[$k]===null?null:(int)$r[$k];return$r;}
}