<?php

declare(strict_types=1);

namespace App\Modules\Property\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Responses\Response;

final class PropertyCatalogMutationController
{
    public function __construct(private PropertyCatalogService $service) {}

    public function createCategory(Request $r): Response { return $this->create($r, ['code','name_ar','name_en'], fn(array $d,int $a) => $this->service->createCategory($d,$a), 'Property Category'); }
    public function updateCategory(Request $r, int|string $id): Response { return $this->update($r,$id,fn($id)=>$this->service->findCategory($id),fn($id,$d,$a)=>$this->service->updateCategory($id,$d,$a),'Property Category'); }
    public function deactivateCategory(Request $r, int|string $id): Response { return $this->action($r,$id,fn($id)=>$this->service->findCategory($id),fn($id,$a)=>$this->service->deactivateCategory($id,$a),'Property Category deactivated.'); }
    public function reactivateCategory(Request $r, int|string $id): Response { return $this->action($r,$id,fn($id)=>$this->service->findCategory($id),fn($id,$a)=>$this->service->reactivateCategory($id,$a),'Property Category reactivated.'); }
    public function deleteCategory(Request $r, int|string $id): Response { return $this->remove($id,fn($id)=>$this->service->findCategory($id),fn($id)=>$this->service->deleteCategory($id),'Property Category'); }

    public function createUnitType(Request $r): Response { return $this->create($r, ['property_category_id','code','name_ar','name_en'], fn(array $d,int $a) => $this->service->createUnitType($d,$a), 'Unit Type'); }
    public function updateUnitType(Request $r, int|string $id): Response { return $this->update($r,$id,fn($id)=>$this->service->findUnitType($id),fn($id,$d,$a)=>$this->service->updateUnitType($id,$d,$a),'Unit Type'); }
    public function deactivateUnitType(Request $r, int|string $id): Response { return $this->action($r,$id,fn($id)=>$this->service->findUnitType($id),fn($id,$a)=>$this->service->deactivateUnitType($id,$a),'Unit Type deactivated.'); }
    public function reactivateUnitType(Request $r, int|string $id): Response { return $this->action($r,$id,fn($id)=>$this->service->findUnitType($id),fn($id,$a)=>$this->service->reactivateUnitType($id,$a),'Unit Type reactivated.'); }
    public function deleteUnitType(Request $r, int|string $id): Response { return $this->remove($id,fn($id)=>$this->service->findUnitType($id),fn($id)=>$this->service->deleteUnitType($id),'Unit Type'); }

    public function createMeasurementDefinition(Request $r): Response { return $this->create($r, ['code','name_ar','name_en','default_unit_code'], fn(array $d,int $a) => $this->service->createMeasurementDefinition($d,$a), 'Measurement Definition'); }
    public function updateMeasurementDefinition(Request $r, int|string $id): Response { return $this->update($r,$id,fn($id)=>$this->service->findMeasurementDefinition($id),fn($id,$d,$a)=>$this->service->updateMeasurementDefinition($id,$d,$a),'Measurement Definition'); }
    public function deactivateMeasurementDefinition(Request $r, int|string $id): Response { return $this->action($r,$id,fn($id)=>$this->service->findMeasurementDefinition($id),fn($id,$a)=>$this->service->deactivateMeasurementDefinition($id,$a),'Measurement Definition deactivated.'); }
    public function reactivateMeasurementDefinition(Request $r, int|string $id): Response { return $this->action($r,$id,fn($id)=>$this->service->findMeasurementDefinition($id),fn($id,$a)=>$this->service->reactivateMeasurementDefinition($id,$a),'Measurement Definition reactivated.'); }
    public function deleteMeasurementDefinition(Request $r, int|string $id): Response { return $this->remove($id,fn($id)=>$this->service->findMeasurementDefinition($id),fn($id)=>$this->service->deleteMeasurementDefinition($id),'Measurement Definition'); }

    public function createAttributeDefinition(Request $r): Response { return $this->create($r, ['code','name_ar','name_en','data_type'], fn(array $d,int $a) => $this->service->createAttributeDefinition($d,$a), 'Attribute Definition'); }
    public function updateAttributeDefinition(Request $r, int|string $id): Response { return $this->update($r,$id,fn($id)=>$this->service->findAttributeDefinition($id),fn($id,$d,$a)=>$this->service->updateAttributeDefinition($id,$d,$a),'Attribute Definition'); }
    public function deactivateAttributeDefinition(Request $r, int|string $id): Response { return $this->action($r,$id,fn($id)=>$this->service->findAttributeDefinition($id),fn($id,$a)=>$this->service->deactivateAttributeDefinition($id,$a),'Attribute Definition deactivated.'); }
    public function reactivateAttributeDefinition(Request $r, int|string $id): Response { return $this->action($r,$id,fn($id)=>$this->service->findAttributeDefinition($id),fn($id,$a)=>$this->service->reactivateAttributeDefinition($id,$a),'Attribute Definition reactivated.'); }
    public function deleteAttributeDefinition(Request $r, int|string $id): Response { return $this->remove($id,fn($id)=>$this->service->findAttributeDefinition($id),fn($id)=>$this->service->deleteAttributeDefinition($id),'Attribute Definition'); }

    public function createAttributeOption(Request $r, int|string $definitionId): Response { return $this->create($r,['code','name_ar','name_en'],fn(array $d,int $a)=>$this->service->createAttributeOption($definitionId,$d,$a),'Attribute Option',fn()=> $this->service->findAttributeDefinition($definitionId)); }
    public function updateAttributeOption(Request $r, int|string $definitionId, int|string $optionId): Response { return $this->option($r,$definitionId,$optionId,fn($d,$o,$data,$a)=>$this->service->updateAttributeOption($d,$o,$data,$a),'Attribute Option updated.'); }
    public function deactivateAttributeOption(Request $r, int|string $definitionId, int|string $optionId): Response { return $this->option($r,$definitionId,$optionId,fn($d,$o,$data,$a)=>$this->service->deactivateAttributeOption($d,$o,$a),'Attribute Option deactivated.'); }
    public function reactivateAttributeOption(Request $r, int|string $definitionId, int|string $optionId): Response { return $this->option($r,$definitionId,$optionId,fn($d,$o,$data,$a)=>$this->service->reactivateAttributeOption($d,$o,$a),'Attribute Option reactivated.'); }
    public function deleteAttributeOption(Request $r, int|string $definitionId, int|string $optionId): Response { return $this->option($r,$definitionId,$optionId,fn($d,$o)=>$this->service->deleteAttributeOption($d,$o),'Attribute Option deleted.'); }

    private function create(Request $r,array $required,callable $operation,string $name,?callable $parent=null): Response { try { if($parent!==null && $parent()===null)return $this->notFound($name); $data=$this->body($r,$required); return Response::success("{$name} created.",$operation($data,$this->actor($r)),[],201); } catch(ValidationException $e){return $this->domain($e);} }
    private function update(Request $r,int|string $id,callable $find,callable $operation,string $name): Response { try { if($find($id)===null)return $this->notFound($name); $record=$operation($id,$this->body($r),$this->actor($r)); return $record===null?$this->notFound($name):Response::success("{$name} updated.",$record); } catch(ValidationException $e){return $this->domain($e);} }
    private function action(Request $r,int|string $id,callable $find,callable $operation,string $message): Response { try { if($find($id)===null)return $this->notFound('Resource'); return Response::success($message,$operation($id,$this->actor($r))); } catch(ValidationException $e){return $this->domain($e);} }
    private function remove(int|string $id,callable $find,callable $operation,string $name): Response { try { if($find($id)===null)return $this->notFound($name); $operation($id); return Response::success("{$name} deleted."); } catch(ValidationException $e){return $this->domain($e);} }
    private function option(Request $r,int|string $definitionId,int|string $optionId,callable $operation,string $message): Response { try { $definition=$this->service->findAttributeDefinition($definitionId); $option=$this->service->findAttributeOption($optionId); if($definition===null||$option===null||(int)$option['attribute_definition_id']!==(int)$definition['id'])return $this->notFound('Attribute Option'); $result=$operation($definitionId,$optionId,$this->body($r),$this->actor($r)); return is_bool($result)?Response::success($message):($result===null?$this->notFound('Attribute Option'):Response::success($message,$result)); } catch(ValidationException $e){return $this->domain($e);} }
    private function body(Request $r,array $required=[]): array { $data=$r->all(); foreach($required as $key){if(!array_key_exists($key,$data)||!is_string($data[$key])&&!is_int($data[$key]))throw new ValidationException([$key=>'Required.']);} return $data; }
    private function actor(Request $r): int { return (int)((array)$r->attribute('auth.user'))['id']; }
    private function notFound(string $name): Response { return Response::error('not_found',"{$name} not found.",404); }
    private function domain(ValidationException $e): Response { $errors=$e->errors(); $code=(string)(array_key_first($errors)??'validation_error'); return Response::json(['success'=>false,'error'=>['code'=>$code,'message'=>'The Property Catalog request is invalid.','fields'=>$errors]],422); }
}
