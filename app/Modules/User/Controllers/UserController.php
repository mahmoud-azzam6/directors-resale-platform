<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\User\Services\UserService;
use App\Responses\Response;

/**
 * Handles HTTP requests for staged User records.
 */
final class UserController
{
    public function __construct(private UserService $service)
    {
    }

    public function index(Request $request): Response
    {
        return Response::success('Users retrieved.', $this->service->all((array) $request->attribute('auth.scope.organization_ids', [])));
    }

    public function administrativeIndex(Request $request): Response
    {
        return Response::success(
            'Administrative User directory retrieved.',
            $this->service->administrativeDirectory((array) $request->attribute('auth.scope.organization_ids', []))
        );
    }

    public function show(Request $request, int|string $id): Response
    {
        $user = $this->service->find($id);

        return $user === null
            ? Response::error('not_found', 'User not found.', 404)
            : Response::success('User retrieved.', $user);
    }

    public function store(Request $request): Response
    {
        try {
            return Response::success('User created.', $this->service->create($request->all()), [], 201);
        } catch (ValidationException $exception) {
            return $this->validationResponse($exception);
        }
    }

    public function update(Request $request, int|string $id): Response
    {
        try {
            $user = $this->service->update($id, $request->all());

            return $user === null
                ? Response::error('not_found', 'User not found.', 404)
                : Response::success('User updated.', $user);
        } catch (ValidationException $exception) {
            return $this->validationResponse($exception);
        }
    }

    public function destroy(Request $request, int|string $id): Response
    {
        return $this->service->deactivate($id)
            ? Response::success('User deactivated.')
            : Response::error('not_found', 'User not found.', 404);
    }

    private function validationResponse(ValidationException $exception): Response
    {
        return Response::json([
            'success' => false,
            'error' => [
                'code' => 'validation_error',
                'message' => 'The user data is invalid.',
                'fields' => $exception->errors(),
            ],
        ], 422);
    }
}
