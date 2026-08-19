<?php

declare(strict_types=1);

namespace App\Modules\Position\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Position\Services\PositionService;
use App\Responses\Response;

/**
 * Handles HTTP requests for dynamic Positions.
 */
final class PositionController
{
    public function __construct(private PositionService $service)
    {
    }

    public function index(Request $request): Response
    {
        return Response::success('Positions retrieved.', $this->service->all((array) $request->attribute('auth.scope.organization_ids', [])));
    }

    public function show(Request $request, int|string $id): Response
    {
        $position = $this->service->find($id);

        return $position === null
            ? Response::error('not_found', 'Position not found.', 404)
            : Response::success('Position retrieved.', $position);
    }

    public function store(Request $request): Response
    {
        try {
            return Response::success('Position created.', $this->service->create($request->all()), [], 201);
        } catch (ValidationException $exception) {
            return $this->validationResponse($exception);
        }
    }

    public function update(Request $request, int|string $id): Response
    {
        try {
            $position = $this->service->update($id, $request->all());

            return $position === null
                ? Response::error('not_found', 'Position not found.', 404)
                : Response::success('Position updated.', $position);
        } catch (ValidationException $exception) {
            return $this->validationResponse($exception);
        }
    }

    public function destroy(Request $request, int|string $id): Response
    {
        return $this->service->deactivate($id)
            ? Response::success('Position deactivated.')
            : Response::error('not_found', 'Position not found.', 404);
    }

    private function validationResponse(ValidationException $exception): Response
    {
        return Response::json([
            'success' => false,
            'error' => [
                'code' => 'validation_error',
                'message' => 'The position data is invalid.',
                'fields' => $exception->errors(),
            ],
        ], 422);
    }
}