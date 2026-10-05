<?php
declare(strict_types=1);
namespace App\Modules\Property\Controllers;
use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Http\UploadedFile;
use App\Modules\Property\Services\PropertyPrimaryImageService;
use App\Responses\Response;

final class PropertyPrimaryImageController
{
    public function __construct(private PropertyPrimaryImageService $images) {}
    public function handle(Request $request,string $id,string $operation): Response
    {
        $organization=(int)$request->attribute('auth.target.organization_id');
        try {
            if ($operation==='content') return Response::webp($this->images->read((int)$id,$organization));
            if ($operation==='replace') {
                $file=$request->file('image');
                if (!$file instanceof UploadedFile || count($request->files())!==1 || $request->all()!==[]) throw new ValidationException(['INVALID_IMAGE_UPLOAD'=>'One image file only is required.']);
                $image=$this->images->replace((int)$id,$organization,(int)((array)$request->attribute('auth.user'))['id'],$file);
            } elseif ($operation==='remove') { $this->images->remove((int)$id,$organization); $image=null; }
            else $image=$this->images->metadata((int)$id,$organization);
            return Response::success('Primary image retrieved.', ['primary_image'=>$image]);
        } catch (ValidationException $e) {
            if (isset($e->errors()['PROPERTY_NOT_FOUND']) || isset($e->errors()['IMAGE_NOT_FOUND'])) return Response::error('not_found','Primary image or Property not found.',404);
            return Response::json(['success'=>false,'error'=>['code'=>'validation_error','message'=>'The primary image is invalid.','fields'=>$e->errors()]],422);
        }
    }
}
