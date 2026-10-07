<?php

declare(strict_types=1);

namespace App\Modules\Request\Services;

use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Authorization\Services\AuthorizationService;
use App\Modules\Listing\Repositories\ListingRepository;
use App\Modules\Listing\Services\ListingService;
use App\Modules\Property\Repositories\OrganizationPropertyRepository;
use App\Modules\Request\Repositories\ListingInterestRequestRepository;

final class ListingInterestRequestService
{
    public function __construct(
        private DatabaseConnectionInterface $database,
        private ListingInterestRequestRepository $requests,
        private ListingRepository $listings,
        private ListingService $listingService,
        private OrganizationPropertyRepository $properties,
        private AuthorizationService $authorization,
        private UlidGeneratorInterface $ulids
    ) {}

    public function catalog(array $scope): array
    {
        $rows = array_filter($this->requests->published($scope), fn (array $row): bool => $this->listingService->currentlyAvailable($row));
        return array_values(array_map(fn (array $row): array => $this->listingService->find((int) $row['id'], (int) $row['organization_id']), $rows));
    }

    public function detail(int $id, array $scope): ?array
    {
        $row = $this->listings->find($id);
        if ($row === null || ! in_array((int) $row['organization_id'], $scope, true) || ! $this->listingService->currentlyAvailable($row)) { return null; }
        return $this->listingService->find($id, (int) $row['organization_id']);
    }

    public function image(int $id, array $scope): ?string
    {
        $row = $this->detail($id, $scope);
        return $row === null ? null : $this->listingService->image($id, (int) $row['organization_id']);
    }

    public function submit(int $id, array $user): array
    {
        // Locate the immutable Property reference before opening the transaction,
        // so a waiting worker does not retain an older repeatable-read snapshot.
        $candidate = $this->listings->find($id);
        if ($candidate === null) { throw new ValidationException(['unavailable' => 'Listing is unavailable.']); }
        return $this->database->transaction(function () use ($id, $user, $candidate): array {
            // Same aggregate lock order as BF017 publication/archive and Property mutations.
            $this->properties->findForUpdate((int) $candidate['organization_property_id'], (int) $candidate['organization_id']);
            $row = $this->listings->find($id, true);
            if ($row === null || $this->authorization->authorize($user, 'requests.create', (int) $row['organization_id']) !== null
                || $this->authorization->authorize($user, 'published_listings.view', (int) $row['organization_id']) !== null
                || ! $this->listingService->currentlyAvailable($row)) {
                throw new ValidationException(['unavailable' => 'Listing is unavailable.']);
            }
            if ($this->requests->existing($id, (int) $user['id'], true) !== null) { throw new ValidationException(['already_requested' => 'A submitted Request already exists.']); }
            return $this->receipt($this->requests->create($this->ulids->generate(), $row, $user));
        });
    }

    public function own(array $user, array $scope, ?int $id = null): array
    {
        return array_map(fn (array $row): array => $this->receipt($row), $this->requests->own($user, $scope, $id));
    }

    private function receipt(array $row): array
    {
        $property = $this->properties->findInOrganization((int) $row['organization_property_id'], (int) $row['listing_organization_id']);
        return ['id' => (int) $row['id'], 'reference' => $row['ulid'], 'listing_id' => (int) $row['listing_id'], 'organization_property_id' => (int) $row['organization_property_id'],
            'status' => $row['status'], 'created_at' => $row['created_at'], 'property' => ['property_code' => $property['property_code'], 'property_label' => $property['property_label']]];
    }
}
