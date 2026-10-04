<?php

declare(strict_types=1);

namespace App\Modules\Organization\Services;

use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Organization\Repositories\OrganizationBasicProfileRepository;
use App\Modules\Organization\Repositories\OrganizationRepository;
use App\Modules\Property\Repositories\GeographicLocationRepository;
use App\Modules\Property\Services\CanonicalGeographyValidator;

final class OrganizationBasicProfileService
{
    public function __construct(
        private OrganizationBasicProfileRepository $profiles,
        private OrganizationRepository $organizations,
        private GeographicLocationRepository $locations,
        private DatabaseConnectionInterface $db,
        private CanonicalGeographyValidator $geographyValidator
    ) {
    }

    public function show(int $organizationId): ?array
    {
        return $this->aggregate($this->profiles->find($organizationId));
    }

    public function save(int $organizationId, array $data, int $actorId): array
    {
        return $this->db->transaction(function () use ($organizationId, $data, $actorId): array {
            if ($this->organizations->find($organizationId) === null) {
                throw new ValidationException(['organization' => 'Organization was not found.']);
            }

            $existing = $this->profiles->find($organizationId);
            $values = $this->values($data + [
                'geographic_location_id' => $existing['geographic_location_id'] ?? null,
                'address_text' => $existing['address_text'] ?? null,
            ], $actorId);
            if (array_key_exists('organization_name', $data)) {
                $name = $data['organization_name'];
                if (!is_string($name) || trim($name) === '' || mb_strlen(trim($name)) > 255) {
                    throw new ValidationException(['organization_name' => 'A name of 1–255 characters is required.']);
                }
                $this->organizations->update($organizationId, ['name' => trim($name)]);
            }

            return $this->aggregate($existing
                ? $this->profiles->update($organizationId, $values)
                : $this->profiles->create($values + [
                    'organization_id' => $organizationId,
                    'created_by_user_id' => $actorId,
                ]));
        });
    }

    private function values(array $data, int $actor): array
    {
        $location = array_key_exists('geographic_location_id', $data) ? $data['geographic_location_id'] : null;
        if ($location !== null) {
            if ((!is_int($location) && !is_string($location)) || preg_match('/^[1-9][0-9]*$/D', (string) $location) !== 1
                || strlen((string) $location) > strlen((string) PHP_INT_MAX)
                || (strlen((string) $location) === strlen((string) PHP_INT_MAX) && strcmp((string) $location, (string) PHP_INT_MAX) > 0)
                || !$this->geographyValidator->accepts((int) $location)) {
                throw new ValidationException(['geographic_location_id' => 'An active canonical geographic hierarchy is required.']);
            }
            $location = (int) $location;
        }

        $address = array_key_exists('address_text', $data) ? $data['address_text'] : null;
        if ($address !== null && (! is_string($address) || trim($address) === '' || mb_strlen(trim($address)) > 1000)) {
            throw new ValidationException(['address_text' => 'Address text must be non-empty when supplied.']);
        }

        return [
            'geographic_location_id' => $location,
            'address_text' => $address === null ? null : trim($address),
            'updated_by_user_id' => $actor,
        ];
    }

    private function aggregate(?array $profile): ?array
    {
        if ($profile === null) {
            return null;
        }
        $profile['geography'] = $profile['geographic_location_id'] === null
            ? null
            : $this->locations->findGeographicLocationById((int) $profile['geographic_location_id']);

        return $profile;
    }
}
