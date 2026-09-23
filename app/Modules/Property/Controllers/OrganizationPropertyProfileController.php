<?php

declare(strict_types=1);

namespace App\Modules\Property\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Property\Services\OrganizationPropertyProfileService;
use App\Responses\Response;

final class OrganizationPropertyProfileController
{
    public function __construct(private OrganizationPropertyProfileService $service) {}

    public function show(Request $request, int|string $id): Response
    {
        try {
            return Response::success('Organization Property Profile retrieved.', $this->service->getProfile($id, $this->target($request)));
        } catch (ValidationException $exception) {
            return $this->response($exception);
        }
    }

    public function update(Request $request, int|string $id): Response
    {
        try {
            return Response::success('Organization Property Profile saved.', $this->service->saveProfile($id, $this->target($request), $request->all(), $this->actor($request)));
        } catch (ValidationException $exception) {
            return $this->response($exception);
        }
    }

    private function target(Request $request): int { return (int) $request->attribute('auth.target.organization_id'); }
    private function actor(Request $request): int { return (int) ((array) $request->attribute('auth.user'))['id']; }
    private function response(ValidationException $exception): Response
    {
        $fields = $exception->errors();
        if (isset($fields['PROPERTY_NOT_FOUND'])) return Response::error('not_found', 'Organization Property not found.', 404);
        return Response::json(['success'=>false,'error'=>['code'=>'validation_error','message'=>'The Organization Property Profile data is invalid.','fields'=>$fields]], 422);
    }
}