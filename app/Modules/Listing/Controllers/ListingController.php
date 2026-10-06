<?php

declare(strict_types=1);

namespace App\Modules\Listing\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Listing\Services\ListingService;
use App\Responses\Response;

final class ListingController
{
    public function __construct(private ListingService $service) {}

    public function index(Request $request): Response
    {
        $target = $request->attribute('auth.target.organization_id');
        $scope = $target === null ? (array) $request->attribute('auth.scope.organization_ids', []) : [(int) $target];
        return Response::success('Listings retrieved.', $this->service->all($scope));
    }

    public function handle(Request $request, ?string $id = null, string $operation = 'show'): Response
    {
        try {
            $organization = (int) $request->attribute('auth.target.organization_id');
            $actor = (int) ((array) $request->attribute('auth.user'))['id'];
            if ($operation === 'image') { return Response::webp($this->service->image((int) $id, $organization)); }
            if ($operation === 'create') {
                $this->onlyFields($request, ['organization_property_id']);
                $data = $this->service->create($this->positiveId($request->input('organization_property_id'), 'organization_property_id'), $organization, $actor);
            } elseif ($operation === 'publish' || $operation === 'archive') {
                $this->onlyFields($request, ['revision']);
                $data = $this->service->transition((int) $id, $organization, $this->positiveId($request->input('revision'), 'revision'), $actor, $operation === 'publish' ? 'published' : 'archived');
            } else {
                $data = $this->service->find((int) $id, $organization);
                if ($data === null) { return Response::error('not_found', 'Listing not found.', 404); }
            }
            return Response::success('Listing retrieved.', $data, [], $operation === 'create' ? 201 : 200);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            if (($errors['revision'] ?? null) === 'REVISION_CONFLICT') { return Response::error('revision_conflict', 'Listing changed. Reload and explicitly retry.', 409); }
            if (isset($errors['IMAGE_NOT_FOUND'])) { return Response::error('not_found', 'Primary image not found.', 404); }
            return Response::json(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Listing data is invalid.', 'fields' => $errors]], 422);
        }
    }

    private function onlyFields(Request $request, array $fields): void
    {
        if (array_diff(array_keys($request->all()), $fields) !== []) { throw new ValidationException(['fields' => 'Unsupported Listing fields.']); }
    }

    private function positiveId(mixed $value, string $field): int
    {
        if ((! is_int($value) && (! is_string($value) || ! ctype_digit($value))) || (int) $value <= 0) {
            throw new ValidationException([$field => 'A positive integer is required.']);
        }
        return (int) $value;
    }
}
