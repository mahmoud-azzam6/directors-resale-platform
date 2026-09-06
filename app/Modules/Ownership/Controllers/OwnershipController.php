<?php

declare(strict_types=1);

namespace App\Modules\Ownership\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Ownership\Services\OwnershipService;
use App\Responses\Response;

final class OwnershipController
{
    public function __construct(private OwnershipService $service) {}

    public function store(Request $request, int|string $propertyId): Response
    {
        return $this->mutate(function () use ($request, $propertyId): Response {
            $data = $request->all();
            if (! is_array($data['parties'] ?? null)) { throw new ValidationException(['parties' => 'Parties must be an array.']); }
            if (array_key_exists('acting_owner', $data) && $data['acting_owner'] !== null && ! is_array($data['acting_owner'])) {
                throw new ValidationException(['acting_owner' => 'Acting Owner must be an object or null.']);
            }
            $data['organization_property_id'] = $propertyId;
            $data['organization_id'] = $this->organization($request);
            return Response::success('Current Ownership recorded.', $this->service->recordCurrent($data, $this->actor($request)), [], 201);
        });
    }

    public function show(Request $request, int|string $id): Response
    {
        return $this->read(fn () => $this->service->find($id, $this->organization($request)), 'Ownership retrieved.', 'Ownership not found.');
    }

    public function currentForProperty(Request $request, int|string $propertyId): Response
    {
        return $this->read(fn () => $this->service->currentForProperty($propertyId, $this->organization($request)), 'Current Ownership retrieved.', 'Current Ownership not found.');
    }

    public function historyForProperty(Request $request, int|string $propertyId): Response
    {
        return $this->mutate(fn (): Response => Response::success('Ownership history retrieved.', $this->service->historyForProperty($propertyId, $this->organization($request))));
    }

    public function parties(Request $request, int|string $id): Response
    {
        return $this->mutate(fn (): Response => Response::success('Ownership Parties retrieved.', $this->service->parties($id, $this->organization($request))));
    }

    public function addParty(Request $request, int|string $id): Response
    {
        return $this->mutate(function () use ($request, $id): Response {
            if (! $request->has('owner_id')) { throw new ValidationException(['owner_id' => 'Owner is required.']); }
            return Response::success('Ownership Party added.', $this->service->addParty($id, $this->organization($request), $request->all(), $this->actor($request)));
        });
    }

    public function updatePartyShare(Request $request, int|string $id, int|string $partyId): Response
    {
        return $this->mutate(function () use ($request, $id, $partyId): Response {
            if (! $request->has('share_percentage')) { throw new ValidationException(['share_percentage' => 'Share percentage is required.']); }
            return Response::success('Ownership Party share updated.', $this->service->updatePartyShare($partyId, $id, $this->organization($request), $request->input('share_percentage'), $this->actor($request)));
        });
    }

    public function removeParty(Request $request, int|string $id, int|string $partyId): Response
    {
        return $this->mutate(function () use ($request, $id, $partyId): Response {
            $this->service->removeParty($partyId, $id, $this->organization($request), $this->actor($request));
            return Response::success('Ownership Party removed.');
        });
    }

    public function close(Request $request, int|string $id): Response
    {
        return $this->mutate(fn (): Response => Response::success('Ownership closed.', $this->service->close($id, $this->organization($request), $this->actor($request))));
    }

    public function currentActingOwner(Request $request, int|string $id): Response
    {
        return $this->read(fn () => $this->service->currentActingOwner($id, $this->organization($request)), 'Current Acting Owner retrieved.', 'Current Acting Owner not found.');
    }

    public function actingOwnerHistory(Request $request, int|string $id): Response
    {
        return $this->mutate(fn (): Response => Response::success('Acting Owner history retrieved.', $this->service->actingOwnerHistory($id, $this->organization($request))));
    }

    public function designateActingOwner(Request $request, int|string $id): Response { return $this->designation($request, $id, false); }
    public function changeActingOwner(Request $request, int|string $id): Response { return $this->designation($request, $id, true); }

    public function clearActingOwner(Request $request, int|string $id): Response
    {
        return $this->mutate(function () use ($request, $id): Response {
            $this->service->clearActingOwner($id, $this->organization($request), $this->actor($request));
            return Response::success('Acting Owner cleared.');
        });
    }

    private function designation(Request $request, int|string $id, bool $replace): Response
    {
        return $this->mutate(function () use ($request, $id, $replace): Response {
            foreach (['ownership_party_id', 'basis_source'] as $field) {
                if (! $request->has($field)) { throw new ValidationException([$field => str_replace('_', ' ', ucfirst($field)) . ' is required.']); }
            }
            if ($request->has('notes') && $request->input('notes') !== null && ! is_scalar($request->input('notes'))) {
                throw new ValidationException(['notes' => 'Notes must be a scalar value or null.']);
            }
            $designation = $replace
                ? $this->service->changeActingOwner($id, $this->organization($request), $request->all(), $this->actor($request))
                : $this->service->designateActingOwner($id, $this->organization($request), $request->all(), $this->actor($request));
            return Response::success($replace ? 'Acting Owner changed.' : 'Acting Owner designated.', $designation);
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
        return Response::json(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'The Ownership data is invalid.', 'fields' => $exception->errors()]], 422);
    }
}
