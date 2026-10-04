<?php

declare(strict_types=1);

namespace App\Modules\Organization\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Organization\Services\OrganizationBasicProfileService;
use App\Responses\Response;

final class OrganizationBasicProfileController
{
    public function __construct(private OrganizationBasicProfileService $service)
    {
    }

    public function show(Request $request, int|string $id): Response
    {
        return Response::success('Organization basic profile retrieved.', [
            'profile' => $this->service->show((int) $id),
        ]);
    }

    public function update(Request $request, int|string $id): Response
    {
        try {
            return Response::success('Organization basic profile updated.', [
                'profile' => $this->service->save((int) $id, $request->all(), (int) $request->attribute('auth.user')['id']),
            ]);
        } catch (ValidationException $exception) {
            return Response::json([
                'success' => false,
                'error' => [
                    'code' => 'validation_error',
                    'message' => 'The Organization basic profile is invalid.',
                    'fields' => $exception->errors(),
                ],
            ], 422);
        }
    }
}
