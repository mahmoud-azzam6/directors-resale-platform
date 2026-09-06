<?php

declare(strict_types=1);

namespace App\Modules\Property\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Property\Services\OrganizationPropertyService;
use App\Responses\Response;

final class OrganizationPropertyController
{
    public function __construct(private OrganizationPropertyService $service) {}

    public function index(Request $request): Response
    {
        return Response::success('Organization Properties retrieved.', $this->service->all($this->scope($request)));
    }

    public function show(Request $request, int|string $id): Response
    {
        try {
            $property = $this->service->find($id, $this->target($request));
            return $property === null
                ? Response::error('not_found', 'Organization Property not found.', 404)
                : Response::success('Organization Property retrieved.', $property);
        } catch (ValidationException $exception) { return $this->validationResponse($exception); }
    }

    public function store(Request $request): Response
    {
        try {
            $data = $request->all();
            if (! is_string($data['property_label'] ?? null)) {
                throw new ValidationException(['property_label' => 'Property label is required.']);
            }
            $data['organization_id'] = $this->target($request);
            return Response::success('Organization Property created.', $this->service->create($data, $this->actor($request)), [], 201);
        } catch (ValidationException $exception) { return $this->validationResponse($exception); }
    }

    public function update(Request $request, int|string $id): Response
    {
        try {
            if ($request->has('property_label') && ! is_string($request->input('property_label'))) {
                throw new ValidationException(['property_label' => 'Property label must be a string.']);
            }
            $property = $this->service->update($id, $this->target($request), $request->all(), $this->actor($request));
            return $property === null
                ? Response::error('not_found', 'Organization Property not found.', 404)
                : Response::success('Organization Property updated.', $property);
        } catch (ValidationException $exception) { return $this->validationResponse($exception); }
    }

    public function archive(Request $request, int|string $id): Response
    {
        return $this->lifecycle($request, fn () => $this->service->archive($id, $this->target($request), $this->actor($request)), 'archived');
    }

    public function reactivate(Request $request, int|string $id): Response
    {
        return $this->lifecycle($request, fn () => $this->service->reactivate($id, $this->target($request), $this->actor($request)), 'reactivated');
    }

    private function lifecycle(Request $request, callable $operation, string $action): Response
    {
        try { return Response::success("Organization Property {$action}.", $operation()); }
        catch (ValidationException $exception) { return $this->validationResponse($exception); }
    }

    /** @return array<int, int> */
    private function scope(Request $request): array
    {
        $target = $request->attribute('auth.target.organization_id');
        return $target === null ? (array) $request->attribute('auth.scope.organization_ids', []) : [(int) $target];
    }

    private function target(Request $request): int { return (int) $request->attribute('auth.target.organization_id'); }
    private function actor(Request $request): int { return (int) ((array) $request->attribute('auth.user'))['id']; }

    private function validationResponse(ValidationException $exception): Response
    {
        return Response::json(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'The Organization Property data is invalid.', 'fields' => $exception->errors()]], 422);
    }
}
