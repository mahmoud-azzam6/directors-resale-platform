<?php

declare(strict_types=1);

namespace App\Modules\Request\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Authorization\Services\AuthorizationService;
use App\Modules\Request\Services\ListingInterestRequestService;
use App\Responses\Response;

final class PublishedListingController
{
    public function __construct(private ListingInterestRequestService $service, private AuthorizationService $authorization) {}

    public function handle(Request $request, ?string $id = null, string $operation = 'catalog'): Response
    {
        $scope = (array) $request->attribute('auth.scope.organization_ids', []);
        $target = $request->attribute('auth.target.organization_id');
        if ($target !== null) { $scope = [(int) $target]; }
        $user = (array) $request->attribute('auth.user');
        if ($id !== null && (! ctype_digit($id) || (int) $id <= 0)) { return Response::error('not_found', 'Resource not found.', 404); }
        try {
            if ($operation === 'submit') {
                $denied = $this->authorization->authorize($user, 'published_listings.view', $target === null ? null : (int) $target);
                if ($denied !== null) { return $denied; }
                if ($request->all() !== []) { return Response::error('validation_error', 'Request fields are server-owned.', 422); }
                return Response::success('Interest submitted.', $this->service->submit((int) $id, $user), [], 201);
            }
            if ($operation === 'own') { return Response::success('Own Requests retrieved.', $this->service->own($user, $scope)); }
            if ($operation === 'receipt') {
                $data = $this->service->own($user, $scope, (int) $id)[0] ?? null;
            } elseif ($operation === 'catalog') {
                return Response::success('Published Listings retrieved.', $this->service->catalog($scope));
            } elseif ($operation === 'image') {
                $bytes = $this->service->image((int) $id, $scope);
                return $bytes === null ? Response::error('listing_unavailable', 'Listing is unavailable.', 404) : Response::webp($bytes);
            } else { $data = $this->service->detail((int) $id, $scope); }
            return $data === null ? Response::error('listing_unavailable', 'Resource is unavailable.', 404) : Response::success('Resource retrieved.', $data);
        } catch (ValidationException $exception) {
            if (isset($exception->errors()['already_requested'])) { return Response::error('already_requested', 'Interest was already submitted.', 409); }
            return Response::error('listing_unavailable', 'Listing is unavailable.', 422);
        }
    }
}
