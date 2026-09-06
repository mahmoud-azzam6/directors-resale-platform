<?php

declare(strict_types=1);

namespace App\Modules\Owner\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Owner\Services\OwnerService;
use App\Responses\Response;

final class OwnerController
{
    private const CONTACT_FIELDS = ['mobile', 'email', 'preferred_contact_method', 'contact_person_name', 'contact_person_mobile', 'contact_person_email'];

    public function __construct(private OwnerService $service) {}

    public function index(Request $request): Response
    {
        try {
            $selectors = array_values(array_filter(['display_name', 'mobile', 'email'], fn (string $key): bool => $request->query($key) !== null));
            if (count($selectors) > 1) { throw new ValidationException(['search' => 'Select only one Owner search field.']); }
            if ($selectors === []) { return Response::success('Owners retrieved.', $this->service->all($this->scope($request))); }
            $scope = $this->scope($request);
            if (count($scope) !== 1) { throw new ValidationException(['organization_id' => 'Organization is required for Owner search.']); }
            $value = $request->query($selectors[0]);
            if (! is_string($value)) { throw new ValidationException([$selectors[0] => 'Search value must be a string.']); }
            $owners = match ($selectors[0]) {
                'display_name' => $this->service->searchByDisplayName($scope[0], $value),
                'mobile' => $this->service->searchByMobile($scope[0], $value),
                'email' => $this->service->searchByEmail($scope[0], $value),
            };
            return Response::success('Owners retrieved.', $owners);
        } catch (ValidationException $exception) { return $this->validationResponse($exception); }
    }

    public function show(Request $request, int|string $id): Response
    {
        try {
            $owner = $this->service->find($id, $this->target($request));
            return $owner === null ? Response::error('not_found', 'Owner not found.', 404) : Response::success('Owner retrieved.', $owner);
        } catch (ValidationException $exception) { return $this->validationResponse($exception); }
    }

    public function store(Request $request): Response
    {
        try {
            $data = $request->all();
            foreach (['party_type', 'display_name'] as $field) {
                if (! is_string($data[$field] ?? null)) { throw new ValidationException([$field => str_replace('_', ' ', ucfirst($field)) . ' is required.']); }
            }
            foreach (self::CONTACT_FIELDS as $field) {
                if (array_key_exists($field, $data) && $data[$field] !== null && ! is_scalar($data[$field])) {
                    throw new ValidationException([$field => str_replace('_', ' ', ucfirst($field)) . ' must be a scalar value or null.']);
                }
            }
            $data['organization_id'] = $this->target($request);
            return Response::success('Owner created.', $this->service->create($data, $this->actor($request)), [], 201);
        } catch (ValidationException $exception) { return $this->validationResponse($exception); }
    }

    public function update(Request $request, int|string $id): Response
    {
        try {
            $owner = $this->service->update($id, $this->target($request), $request->all(), $this->actor($request));
            return $owner === null ? Response::error('not_found', 'Owner not found.', 404) : Response::success('Owner updated.', $owner);
        } catch (ValidationException $exception) { return $this->validationResponse($exception); }
    }

    public function deactivate(Request $request, int|string $id): Response { return $this->lifecycle($request, fn () => $this->service->deactivate($id, $this->target($request), $this->actor($request)), 'deactivated'); }
    public function reactivate(Request $request, int|string $id): Response { return $this->lifecycle($request, fn () => $this->service->reactivate($id, $this->target($request), $this->actor($request)), 'reactivated'); }

    private function lifecycle(Request $request, callable $operation, string $action): Response
    {
        try { return Response::success("Owner {$action}.", $operation()); }
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
        return Response::json(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'The Owner data is invalid.', 'fields' => $exception->errors()]], 422);
    }
}
