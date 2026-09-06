<?php

declare(strict_types=1);

namespace App\Modules\Property\Services;

use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Property\Repositories\OrganizationPropertyRepository;
use App\Modules\Property\Repositories\PropertyOwnerLifecycleHistoryRepository;
use RuntimeException;

final class OrganizationPropertyService
{
    public function __construct(
        private OrganizationPropertyRepository $repository,
        private PropertyOwnerLifecycleHistoryRepository $historyRepository,
        private DatabaseConnectionInterface $database,
        private UlidGeneratorInterface $ulidGenerator
    ) {
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function create(array $data, int|string $userId): array
    {
        $organizationId = $this->positiveIdentifier($data['organization_id'] ?? null, 'organization_id');
        $actorId = $this->positiveIdentifier($userId, 'user_id');
        $propertyLabel = $this->requiredString($data['property_label'] ?? null, 'property_label');

        return $this->repository->create([
            'ulid' => $this->ulidGenerator->generate(),
            'organization_id' => $organizationId,
            'property_label' => $propertyLabel,
            'status' => 'active',
            'created_by_user_id' => $actorId,
            'updated_by_user_id' => $actorId,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function find(int|string $id, int|string $organizationId): ?array
    {
        $propertyId = $this->positiveIdentifier($id, 'property_id');
        $scopedOrganizationId = $this->positiveIdentifier($organizationId, 'organization_id');

        return $this->repository->findInOrganization($propertyId, $scopedOrganizationId);
    }

    /** @param array<int, int> $organizationIds @return array<int, array<string, mixed>> */
    public function all(array $organizationIds): array
    {
        if ($organizationIds === []) {
            return [];
        }

        $validatedIds = array_map(
            fn (mixed $organizationId): int => $this->positiveIdentifier($organizationId, 'organization_id'),
            $organizationIds
        );

        return $this->repository->allInScope($validatedIds);
    }

    /** @param array<string, mixed> $data @return array<string, mixed>|null */
    public function update(
        int|string $id,
        int|string $organizationId,
        array $data,
        int|string $userId
    ): ?array {
        $propertyId = $this->positiveIdentifier($id, 'property_id');
        $scopedOrganizationId = $this->positiveIdentifier($organizationId, 'organization_id');
        $actorId = $this->positiveIdentifier($userId, 'user_id');

        if ($this->repository->findInOrganization($propertyId, $scopedOrganizationId) === null) {
            return null;
        }

        $attributes = ['updated_by_user_id' => $actorId];

        if (array_key_exists('property_label', $data)) {
            $attributes['property_label'] = $this->requiredString($data['property_label'], 'property_label');
        }

        return $this->repository->updateStoredFields($propertyId, $scopedOrganizationId, $attributes);
    }

    /** @return array<string, mixed> */
    public function archive(int|string $id, int|string $organizationId, int|string $userId): array
    {
        return $this->changeLifecycle($id, $organizationId, $userId, 'active', 'archived', 'property_archive');
    }

    /** @return array<string, mixed> */
    public function reactivate(int|string $id, int|string $organizationId, int|string $userId): array
    {
        return $this->changeLifecycle($id, $organizationId, $userId, 'archived', 'active', 'property_reactivate');
    }

    /** @return array<string, mixed> */
    private function changeLifecycle(
        int|string $id,
        int|string $organizationId,
        int|string $userId,
        string $fromStatus,
        string $toStatus,
        string $action
    ): array {
        $propertyId = $this->positiveIdentifier($id, 'property_id');
        $scopedOrganizationId = $this->positiveIdentifier($organizationId, 'organization_id');
        $actorId = $this->positiveIdentifier($userId, 'user_id');

        return $this->database->transaction(function () use (
            $propertyId, $scopedOrganizationId, $actorId, $fromStatus, $toStatus, $action
        ): array {
            $property = $this->repository->findForUpdate($propertyId, $scopedOrganizationId);

            if ($property === null) {
                throw new ValidationException(['property' => 'Organization Property was not found.']);
            }

            if (($property['status'] ?? null) !== $fromStatus) {
                throw new ValidationException(['status' => sprintf(
                    'Organization Property must be %s before it can become %s.',
                    $fromStatus,
                    $toStatus
                )]);
            }

            $timestamp = date('Y-m-d H:i:s');
            $updated = $toStatus === 'archived'
                ? $this->repository->archive($propertyId, $scopedOrganizationId, $timestamp, $actorId)
                : $this->repository->reactivate($propertyId, $scopedOrganizationId, $actorId);

            if ($updated === null) {
                throw new RuntimeException('Organization Property lifecycle persistence failed.');
            }

            $this->historyRepository->appendPropertyEvent([
                'ulid' => $this->ulidGenerator->generate(),
                'organization_id' => $scopedOrganizationId,
                'organization_property_id' => $propertyId,
                'action' => $action,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'created_by_user_id' => $actorId,
                'created_at' => $timestamp,
            ]);

            return $updated;
        });
    }

    private function positiveIdentifier(mixed $value, string $field): int
    {
        if ((! is_int($value) && (! is_string($value) || ! ctype_digit($value))) || (int) $value <= 0) {
            throw new ValidationException([$field => str_replace('_', ' ', ucfirst($field)) . ' must be a valid identifier.']);
        }

        return (int) $value;
    }

    private function requiredString(mixed $value, string $field): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new ValidationException([$field => str_replace('_', ' ', ucfirst($field)) . ' is required.']);
        }

        return trim($value);
    }
}
