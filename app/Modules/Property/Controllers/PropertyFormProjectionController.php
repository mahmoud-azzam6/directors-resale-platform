<?php
declare(strict_types=1);
namespace App\Modules\Property\Controllers;
use App\Exceptions\ValidationException;use App\Http\Request;use App\Modules\Property\Services\PropertyFormProjectionService;use App\Responses\Response;
final class PropertyFormProjectionController{
 public function __construct(private PropertyFormProjectionService $service){}
 public function unitTypePropertyForm(Request $request,int|string $id):Response{try{$projection=$this->service->projectForUnitType($id);return $projection===null?Response::error('not_found','Unit Type not found.',404):Response::success('Property form retrieved.',$projection);}catch(ValidationException $e){return $this->domain($e);}}
 public function coreForm(Request $request):Response{return Response::success('Core property form retrieved.',$this->service->getCoreForm());}
 private function domain(ValidationException $e):Response{$errors=$e->errors();$code=(string)(array_key_first($errors)??'validation_error');return Response::json(['success'=>false,'error'=>['code'=>$code,'message'=>'The Property Form request is invalid.','fields'=>$errors]],422);}
}
