<?php

declare(strict_types=1);

namespace App\Modules\Organization\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Organization\Services\OrganizationService;
use App\Responses\Response;

/**
 * Handles HTTP requests for organizations.
 */
final class OrganizationController
{
    public function __construct(private OrganizationService $service)
    {
    }

    public function index(Request $request): Response
    {
        return Response::success('Organizations retrieved.', $this->service->all((array) $request->attribute('auth.scope.organization_ids', [])));
    }

    public function show(Request $request, int|string $id): Response
    {
        $organization = $this->service->find($id);

        return $organization === null
            ? Response::error('not_found', 'Organization not found.', 404)
            : Response::success('Organization retrieved.', $organization);
    }

    public function store(Request $request): Response
    {
        try {
            return Response::success(
                'Organization created.',
                $this->service->create($request->all()),
                [],
                201
            );
        } catch (ValidationException $exception) {
            return $this->validationResponse($exception);
        }
    }

    public function update(Request $request, int|string $id): Response
    {
        try {
            $organization = $this->service->update($id, $request->all());

            return $organization === null
                ? Response::error('not_found', 'Organization not found.', 404)
                : Response::success('Organization updated.', $organization);
        } catch (ValidationException $exception) {
            return $this->validationResponse($exception);
        }
    }

    public function destroy(Request $request, int|string $id): Response
    {
        return $this->service->delete($id)
            ? Response::success('Organization deleted.')
            : Response::error('not_found', 'Organization not found.', 404);
    }

    private function validationResponse(ValidationException $exception): Response
    {
        return Response::json([
            'success' => false,
            'error' => [
                'code' => 'validation_error',
                'message' => 'The organization data is invalid.',
                'fields' => $exception->errors(),
            ],
        ], 422);
    }
}
