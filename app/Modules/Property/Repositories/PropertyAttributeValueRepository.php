<?php
declare(strict_types=1);
namespace App\Modules\Property\Repositories;
use App\Core\Database\QueryBuilderInterface;
use InvalidArgumentException;
use RuntimeException;
final class PropertyAttributeValueRepository {
 private const C=['id','organization_property_id','attribute_definition_id','data_type','value_integer','value_decimal','value_boolean','value_text','value_date','attribute_option_id','created_by_user_id','updated_by_user_id','created_at','updated_at'];
 private const W=['organization_property_id','attribute_definition_id','data_type','value_integer','value_decimal','value_boolean','value_text','value_date','attribute_option_id','created_by_user_id','updated_by_user_id'];
 public function __construct(private QueryBuilderInterface $q){}
 public function listForProperty(int|string $p):array{return array_map(fn($r)=>$this->map($r),$this->q->table('property_attribute_values')->select(self::C)->where('organization_property_id','=',$this->id($p))->orderBy('attribute_definition_id')->get());}
 public function findByPropertyAndDefinition(int|string $p,int|string $d):?array{$r=$this->q->table('property_attribute_values')->select(self::C)->where('organization_property_id','=',$this->id($p))->where('attribute_definition_id','=',$this->id($d))->first();return$r===null?null:$this->map($r);}
 public function upsert(array $a):array{foreach(array_keys($a)as$k)if(!in_array($k,self::W,true))throw new InvalidArgumentException('Unknown attribute field.');foreach(self::W as$k)if(!array_key_exists($k,$a))$a[$k]=null;foreach(['organization_property_id','attribute_definition_id','created_by_user_id','updated_by_user_id']as$k)if($a[$k]===null)throw new InvalidArgumentException('Missing attribute field.');$e=$this->findByPropertyAndDefinition($a['organization_property_id'],$a['attribute_definition_id']);if($e===null){$id=$this->q->table('property_attribute_values')->insert($a);$r=$this->q->table('property_attribute_values')->select(self::C)->where('id','=',$id)->first();if($r===null)throw new RuntimeException('Attribute missing.');return$this->map($r);}unset($a['organization_property_id'],$a['attribute_definition_id'],$a['created_by_user_id']);$this->q->table('property_attribute_values')->where('id','=',$e['id'])->update($a);return$this->findByPropertyAndDefinition($e['organization_property_id'],$e['attribute_definition_id'])??throw new RuntimeException('Attribute missing.');}
 public function delete(int|string $p,int|string $d):bool{return$this->q->table('property_attribute_values')->where('organization_property_id','=',$this->id($p))->where('attribute_definition_id','=',$this->id($d))->delete()>0;}
 public function deleteAllForProperty(int|string $p):int{return$this->q->table('property_attribute_values')->where('organization_property_id','=',$this->id($p))->delete();}
 private function id(int|string $v):int{if((!is_int($v)&&(!is_string($v)||!ctype_digit($v)))||(int)$v<1)throw new InvalidArgumentException('Invalid identifier.');return(int)$v;}
 private function map(array $r):array{foreach(['id','organization_property_id','attribute_definition_id','attribute_option_id','created_by_user_id','updated_by_user_id']as$k)$r[$k]=$r[$k]===null?null:(int)$r[$k];$r['value_integer']=$r['value_integer']===null?null:(int)$r['value_integer'];$r['value_boolean']=$r['value_boolean']===null?null:(bool)$r['value_boolean'];return$r;}
}