<?php

declare(strict_types=1);

namespace App\Modules\Listing\Services;

use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Listing\Repositories\ListingRepository;
use App\Modules\Organization\Repositories\OrganizationRepository;
use App\Modules\Property\Repositories\OrganizationPropertyRepository;
use App\Modules\Property\Services\CanonicalGeographyValidator;
use App\Modules\Property\Services\PropertyPrimaryImageService;

final class ListingService
{
    public function __construct(
        private DatabaseConnectionInterface $database,
        private ListingRepository $listings,
        private OrganizationPropertyRepository $properties,
        private OrganizationRepository $organizations,
        private CanonicalGeographyValidator $geography,
        private PropertyPrimaryImageService $images,
        private UlidGeneratorInterface $ulids
    ) {}

    public function all(array $scope): array
    {
        return array_map(fn (array $listing): array => $this->present($listing), $this->listings->allInScope($scope));
    }

    public function find(int $id, int $organization): ?array
    {
        $listing = $this->listings->find($id);
        return $listing === null || (int) $listing['organization_id'] !== $organization ? null : $this->present($listing);
    }

    public function create(int $propertyId, int $organization, int $actor): array
    {
        return $this->database->transaction(function () use ($propertyId, $organization, $actor): array {
            $property = $this->properties->findForUpdate($propertyId, $organization);
            $this->activeProperty($property, $organization);
            if ($this->listings->activeForProperty($propertyId) !== null) { $this->fail('organization_property_id', 'An active Listing already exists for this Property.'); }
            return $this->present($this->listings->create($this->ulids->generate(), $propertyId, $organization, $actor));
        });
    }

    public function transition(int $id, int $organization, int $revision, int $actor, string $status): array
    {
        return $this->database->transaction(function () use ($id, $organization, $revision, $actor, $status): array {
            $candidate = $this->listings->find($id);
            if ($candidate === null || (int) $candidate['organization_id'] !== $organization) { $this->fail('listing', 'Listing not found.'); }
            // Same aggregate lock order as Property/Profile/Ownership/image mutations.
            $property = $this->properties->findForUpdate((int) $candidate['organization_property_id'], $organization);
            $listing = $this->listings->find($id, true);
            if ((int) $listing['revision'] !== $revision) { $this->fail('revision', 'REVISION_CONFLICT'); }
            if ($status === 'published') {
                if ($listing['status'] !== 'draft') { $this->fail('status', 'Only a draft can be published.'); }
                $this->activeProperty($property, $organization);
                $errors = $this->publicationErrors($property, $organization);
                if ($errors !== []) { throw new ValidationException($errors); }
            } elseif ($status !== 'archived' || $listing['status'] === 'archived') {
                $this->fail('status', 'Listing transition is not allowed.');
            }
            return $this->present($this->listings->transition($id, $status, $actor));
        });
    }

    public function image(int $id, int $organization): string
    {
        $listing = $this->listings->find($id);
        if ($listing === null || (int) $listing['organization_id'] !== $organization) { $this->fail('listing', 'Listing not found.'); }
        return $this->images->read((int) $listing['organization_property_id'], $organization);
    }

    private function activeProperty(?array $property, int $organization): void
    {
        $owner = $this->organizations->find($organization);
        if ($property === null || $property['status'] !== 'active' || $owner === null || $owner['status'] !== 'active'
            || ! in_array($owner['organization_type'], ['franchise', 'partner_agency'], true)) {
            $this->fail('organization_property_id', 'An active Franchise or Partner Property is required.');
        }
    }

    private function publicationErrors(array $property, int $organization): array
    {
        $id = (int) $property['id'];
        $profile = $this->listings->profile($id, $organization) ?? [];
        $errors = [];
        if (! preg_match('/^PROP-[0-9A-HJKMNP-TV-Z]{26}$/D', (string) ($property['property_code'] ?? ''))) { $errors['property_code'] = 'Valid Property code is required.'; }
        if (trim((string) ($profile['address_text'] ?? '')) === '' || empty($profile['geographic_location_id'])
            || ! $this->geography->accepts((int) $profile['geographic_location_id'])) { $errors['address'] = 'A valid canonical location and address are required.'; }
        if (! is_numeric($profile['initial_asking_price'] ?? null) || (float) $profile['initial_asking_price'] <= 0) { $errors['price'] = 'Positive asking price is required.'; }
        if (! in_array($profile['currency_code'] ?? null, ['EGP', 'USD', 'SAR', 'AED'], true)) { $errors['currency'] = 'Supported currency is required.'; }
        try {
            $bytes = $this->images->read($id, $organization);
            $size = @getimagesizefromstring($bytes);
            if ($size === false || ($size['mime'] ?? null) !== 'image/webp') { $errors['image'] = 'A usable primary Property image is required.'; }
        } catch (\Throwable) { $errors['image'] = 'A readable primary Property image is required.'; }
        if (! $this->listings->validOwnership($id, $organization)) { $errors['ownership'] = 'Current Ownership with an active Owner is required.'; }
        return $errors;
    }

    private function present(array $listing): array
    {
        $organization = (int) $listing['organization_id'];
        $property = $this->properties->findInOrganization((int) $listing['organization_property_id'], $organization);
        $profile = $this->listings->profile((int) $property['id'], $organization) ?? [];
        $image = $this->images->metadata((int) $property['id'], $organization);
        foreach (['id', 'organization_id', 'organization_property_id', 'revision'] as $field) { $listing[$field] = (int) $listing[$field]; }
        unset($listing['active_property_guard']);
        $owner = $this->organizations->find($organization);
        $listing['organization'] = ['id' => $organization, 'name' => $owner['name'], 'organization_type' => $owner['organization_type']];
        $listing['property'] = [
            'id' => (int) $property['id'], 'property_code' => $property['property_code'], 'property_label' => $property['property_label'],
            'address_text' => $profile['address_text'] ?? null,
            'location' => empty($profile['geographic_location_id']) ? null : $this->listings->location((int) $profile['geographic_location_id']),
            'initial_asking_price' => $profile['initial_asking_price'] ?? null, 'currency_code' => $profile['currency_code'] ?? null,
            'primary_image_url' => $image === null ? null : '/listings/' . $listing['id'] . '/primary-image/content',
        ];
        return $listing;
    }

    private function fail(string $field, string $message): never { throw new ValidationException([$field => $message]); }
}
