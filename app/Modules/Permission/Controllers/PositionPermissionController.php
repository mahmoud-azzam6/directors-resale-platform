<?php

declare(strict_types=1);

namespace App\Modules\Permission\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Permission\Services\PositionPermissionService;
use App\Responses\Response;

final class PositionPermissionController
{
    public function __construct(private PositionPermissionService $service) {}
    public function index(Request $request, int|string $id): Response
    { return Response::success('Position permissions retrieved.', $this->service->list($id)); }
    public function update(Request $request, int|string $id): Response
    {
        try { return Response::success('Position permissions updated.', $this->service->replace($id, (array) ($request->input('permissions') ?? $request->all()))); }
        catch (ValidationException $exception) { return Response::json(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'The permission assignment is invalid.', 'fields' => $exception->errors()]], 422); }
    }
}