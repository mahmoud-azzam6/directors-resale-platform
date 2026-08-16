<?php

declare(strict_types=1);

namespace App\Modules\PartnerAgency\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\PartnerAgency\Services\PartnerAgencyService;
use App\Responses\Response;

/**
 * Handles HTTP requests for Organization-backed Partner Agency records.
 */
final class PartnerAgencyController
{
    public function __construct(private PartnerAgencyService $service)
    {
    }

    public function index(Request $request): Response
    {
        return Response::success('Partner Agencies retrieved.', $this->service->all());
    }

    public function show(Request $request, int|string $id): Response
    {
        $partnerAgency = $this->service->find($id);

        return $partnerAgency === null
            ? Response::error('not_found', 'Partner Agency not found.', 404)
            : Response::success('Partner Agency retrieved.', $partnerAgency);
    }

    public function store(Request $request): Response
    {
        try {
            return Response::success(
                'Partner Agency created.',
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
            $partnerAgency = $this->service->update($id, $request->all());

            return $partnerAgency === null
                ? Response::error('not_found', 'Partner Agency not found.', 404)
                : Response::success('Partner Agency updated.', $partnerAgency);
        } catch (ValidationException $exception) {
            return $this->validationResponse($exception);
        }
    }

    public function destroy(Request $request, int|string $id): Response
    {
        return $this->service->archive($id)
            ? Response::success('Partner Agency archived.')
            : Response::error('not_found', 'Partner Agency not found.', 404);
    }

    private function validationResponse(ValidationException $exception): Response
    {
        return Response::json([
            'success' => false,
            'error' => [
                'code' => 'validation_error',
                'message' => 'The partner agency data is invalid.',
                'fields' => $exception->errors(),
            ],
        ], 422);
    }
}
