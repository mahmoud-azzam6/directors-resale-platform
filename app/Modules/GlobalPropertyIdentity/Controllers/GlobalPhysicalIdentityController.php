<?php

declare(strict_types=1);

namespace App\Modules\GlobalPropertyIdentity\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\GlobalPropertyIdentity\Services\GlobalPhysicalIdentityService;
use App\Responses\Response;

final class GlobalPhysicalIdentityController
{
    public function __construct(private GlobalPhysicalIdentityService $service) {}

    public function index(Request $request): Response { return Response::success('Global Physical Identities retrieved.', $this->service->all()); }
    public function store(Request $request): Response { return Response::success('Global Physical Identity created.', $this->service->create($this->actor($request)), [], 201); }

    public function show(Request $request, int|string $id): Response
    {
        return $this->read(fn () => $this->service->find($id), 'Global Physical Identity retrieved.', 'Global Physical Identity not found.');
    }

    public function representations(Request $request, int|string $id): Response
    {
        return $this->mutate(function () use ($id): Response {
            if ($this->service->find($id) === null) {
                return Response::error('not_found', 'Global Physical Identity not found.', 404);
            }
            return Response::success('Global Physical Identity representations retrieved.', $this->service->representationsForIdentity($id));
        });
    }

    public function activeLink(Request $request, int|string $propertyId): Response
    {
        return $this->read(fn () => $this->service->activeLinkForProperty($propertyId, $this->organization($request)), 'Global Physical Identity link retrieved.', 'Global Physical Identity link not found.');
    }

    public function linkHistory(Request $request, int|string $propertyId): Response
    {
        return $this->mutate(fn (): Response => Response::success('Global Physical Identity link history retrieved.', $this->service->linkHistoryForProperty($propertyId, $this->organization($request))));
    }

    public function link(Request $request, int|string $propertyId): Response
    {
        return $this->mutate(function () use ($request, $propertyId): Response {
            if (! $request->has('global_physical_property_identity_id')) {
                throw new ValidationException(['global_physical_property_identity_id' => 'Global Physical Identity is required.']);
            }
            return Response::success('Global Physical Identity linked.', $this->service->link($propertyId, $this->organization($request), $request->input('global_physical_property_identity_id'), $this->actor($request)), [], 201);
        });
    }

    public function unlink(Request $request, int|string $propertyId): Response
    {
        return $this->mutate(function () use ($request, $propertyId): Response {
            if (! is_string($request->input('reason'))) { throw new ValidationException(['reason' => 'Unlink reason is required.']); }
            return Response::success('Global Physical Identity unlinked.', $this->service->unlink($propertyId, $this->organization($request), $request->input('reason'), $this->actor($request)));
        });
    }

    public function relink(Request $request, int|string $propertyId): Response
    {
        return $this->mutate(function () use ($request, $propertyId): Response {
            if (! $request->has('global_physical_property_identity_id')) { throw new ValidationException(['global_physical_property_identity_id' => 'Global Physical Identity is required.']); }
            if (! is_string($request->input('reason'))) { throw new ValidationException(['reason' => 'Relink reason is required.']); }
            return Response::success('Global Physical Identity relinked.', $this->service->relink($propertyId, $this->organization($request), $request->input('global_physical_property_identity_id'), $request->input('reason'), $this->actor($request)));
        });
    }

    private function read(callable $operation, string $message, string $missing): Response
    {
        try { $record = $operation(); return $record === null ? Response::error('not_found', $missing, 404) : Response::success($message, $record); }
        catch (ValidationException $exception) { return $this->validationResponse($exception); }
    }
    private function mutate(callable $operation): Response
    {
        try { return $operation(); }
        catch (ValidationException $exception) { return $this->validationResponse($exception); }
    }
    private function organization(Request $request): int { return (int) $request->attribute('auth.target.organization_id'); }
    private function actor(Request $request): int { return (int) ((array) $request->attribute('auth.user'))['id']; }
    private function validationResponse(ValidationException $exception): Response
    {
        return Response::json(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'The Global Physical Identity data is invalid.', 'fields' => $exception->errors()]], 422);
    }
}
