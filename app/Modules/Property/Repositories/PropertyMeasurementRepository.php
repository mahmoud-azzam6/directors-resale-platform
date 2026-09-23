<?php
declare(strict_types=1);
namespace App\Modules\Property\Repositories;
use App\Core\Database\QueryBuilderInterface;
use InvalidArgumentException;
use RuntimeException;
final class PropertyMeasurementRepository {
 private const C=['id','organization_property_id','measurement_definition_id','value_decimal','unit_code','created_by_user_id','updated_by_user_id','created_at','updated_at'];
 public function __construct(private QueryBuilderInterface $q){}
 public function listForProperty(int|string $p):array{return array_map(fn($r)=>$this->map($r),$this->q->table('property_measurements')->select(self::C)->where('organization_property_id','=',$this->id($p))->orderBy('measurement_definition_id')->get());}
 public function findByPropertyAndDefinition(int|string $p,int|string $d):?array{$r=$this->q->table('property_measurements')->select(self::C)->where('organization_property_id','=',$this->id($p))->where('measurement_definition_id','=',$this->id($d))->first();return$r===null?null:$this->map($r);}
 public function upsert(array $a):array{$need=['organization_property_id','measurement_definition_id','value_decimal','unit_code','created_by_user_id','updated_by_user_id'];foreach(array_keys($a)as$k)if(!in_array($k,$need,true))throw new InvalidArgumentException('Unknown measurement field.');foreach($need as$k)if(!array_key_exists($k,$a))throw new InvalidArgumentException('Missing measurement field.');$e=$this->findByPropertyAndDefinition($a['organization_property_id'],$a['measurement_definition_id']);if($e===null){$id=$this->q->table('property_measurements')->insert($a);$r=$this->q->table('property_measurements')->select(self::C)->where('id','=',$id)->first();if($r===null)throw new RuntimeException('Measurement missing.');return$this->map($r);}$this->q->table('property_measurements')->where('id','=',$e['id'])->update(['value_decimal'=>$a['value_decimal'],'unit_code'=>$a['unit_code'],'updated_by_user_id'=>$a['updated_by_user_id']]);return$this->findByPropertyAndDefinition($a['organization_property_id'],$a['measurement_definition_id'])??throw new RuntimeException('Measurement missing.');}
 public function delete(int|string $p,int|string $d):bool{return$this->q->table('property_measurements')->where('organization_property_id','=',$this->id($p))->where('measurement_definition_id','=',$this->id($d))->delete()>0;}
 public function deleteAllForProperty(int|string $p):int{return$this->q->table('property_measurements')->where('organization_property_id','=',$this->id($p))->delete();}
 private function id(int|string $v):int{if((!is_int($v)&&(!is_string($v)||!ctype_digit($v)))||(int)$v<1)throw new InvalidArgumentException('Invalid identifier.');return(int)$v;}
 private function map(array $r):array{foreach(['id','organization_property_id','measurement_definition_id','created_by_user_id','updated_by_user_id']as$k)$r[$k]=(int)$r[$k];return$r;}
}