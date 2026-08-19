<?php

declare(strict_types=1);

namespace App\Modules\Franchise\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Franchise\Services\FranchiseService;
use App\Responses\Response;

/**
 * Handles HTTP requests for Organization-backed Franchise records.
 */
final class FranchiseController
{
    public function __construct(private FranchiseService $service)
    {
    }

    public function index(Request $request): Response
    {
        return Response::success('Franchises retrieved.', $this->service->all((array) $request->attribute('auth.scope.organization_ids', [])));
    }

    public function show(Request $request, int|string $id): Response
    {
        $franchise = $this->service->find($id);

        return $franchise === null
            ? Response::error('not_found', 'Franchise not found.', 404)
            : Response::success('Franchise retrieved.', $franchise);
    }

    public function store(Request $request): Response
    {
        try {
            return Response::success(
                'Franchise created.',
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
            $franchise = $this->service->update($id, $request->all());

            return $franchise === null
                ? Response::error('not_found', 'Franchise not found.', 404)
                : Response::success('Franchise updated.', $franchise);
        } catch (ValidationException $exception) {
            return $this->validationResponse($exception);
        }
    }

    public function destroy(Request $request, int|string $id): Response
    {
        return $this->service->archive($id)
            ? Response::success('Franchise archived.')
            : Response::error('not_found', 'Franchise not found.', 404);
    }

    private function validationResponse(ValidationException $exception): Response
    {
        return Response::json([
            'success' => false,
            'error' => [
                'code' => 'validation_error',
                'message' => 'The franchise data is invalid.',
                'fields' => $exception->errors(),
            ],
        ], 422);
    }
}
