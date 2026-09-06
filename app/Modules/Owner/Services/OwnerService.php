<?php

declare(strict_types=1);

namespace App\Modules\Owner\Services;

use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Owner\Repositories\OwnerRepository;
use App\Modules\Property\Repositories\PropertyOwnerLifecycleHistoryRepository;
use RuntimeException;

final class OwnerService
{
    private const PARTY_TYPES = ['individual', 'legal_entity'];
    private const CONTACT_METHODS = ['phone', 'whatsapp', 'email'];
    private const EDITABLE_FIELDS = [
        'party_type', 'display_name', 'mobile', 'email', 'preferred_contact_method',
        'contact_person_name', 'contact_person_mobile', 'contact_person_email',
    ];

    public function __construct(
        private OwnerRepository $repository,
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
        $attributes = $this->normalizeEditable($data);
        $this->validateOwner($attributes);

        return $this->repository->create(array_merge($attributes, [
            'ulid' => $this->ulidGenerator->generate(),
            'organization_id' => $organizationId,
            'status' => 'active',
            'created_by_user_id' => $actorId,
            'updated_by_user_id' => $actorId,
        ]));
    }

    /** @return array<string, mixed>|null */
    public function find(int|string $id, int|string $organizationId): ?array
    {
        $ownerId = $this->positiveIdentifier($id, 'owner_id');
        $scopedOrganizationId = $this->positiveIdentifier($organizationId, 'organization_id');

        return $this->repository->findInOrganization($ownerId, $scopedOrganizationId);
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

    /** @return array<int, array<string, mixed>> */
    public function searchByDisplayName(int|string $organizationId, string $displayName): array
    {
        $scopedOrganizationId = $this->positiveIdentifier($organizationId, 'organization_id');

        return $this->repository->searchByDisplayName(
            $scopedOrganizationId,
            $this->requiredString($displayName, 'display_name')
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function searchByMobile(int|string $organizationId, string $mobile): array
    {
        $scopedOrganizationId = $this->positiveIdentifier($organizationId, 'organization_id');

        return $this->repository->findByMobileNormalized(
            $scopedOrganizationId,
            $this->requiredString($mobile, 'mobile')
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function searchByEmail(int|string $organizationId, string $email): array
    {
        $scopedOrganizationId = $this->positiveIdentifier($organizationId, 'organization_id');

        return $this->repository->findByEmailNormalized(
            $scopedOrganizationId,
            strtolower($this->requiredString($email, 'email'))
        );
    }

    /** @param array<string, mixed> $data @return array<string, mixed>|null */
    public function update(
        int|string $id,
        int|string $organizationId,
        array $data,
        int|string $userId
    ): ?array {
        $ownerId = $this->positiveIdentifier($id, 'owner_id');
        $scopedOrganizationId = $this->positiveIdentifier($organizationId, 'organization_id');
        $actorId = $this->positiveIdentifier($userId, 'user_id');
        $existing = $this->repository->findInOrganization($ownerId, $scopedOrganizationId);
        if ($existing === null) {
            return null;
        }

        $attributes = $this->normalizeEditable($data);
        $merged = array_merge($existing, $attributes);
        $this->validateOwner($merged);
        $attributes['updated_by_user_id'] = $actorId;

        return $this->repository->updateStoredFields($ownerId, $scopedOrganizationId, $attributes);
    }

    /** @return array<string, mixed> */
    public function deactivate(int|string $id, int|string $organizationId, int|string $userId): array
    {
        return $this->changeLifecycle($id, $organizationId, $userId, 'active', 'inactive', 'owner_deactivate');
    }

    /** @return array<string, mixed> */
    public function reactivate(int|string $id, int|string $organizationId, int|string $userId): array
    {
        return $this->changeLifecycle($id, $organizationId, $userId, 'inactive', 'active', 'owner_reactivate');
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
        $ownerId = $this->positiveIdentifier($id, 'owner_id');
        $scopedOrganizationId = $this->positiveIdentifier($organizationId, 'organization_id');
        $actorId = $this->positiveIdentifier($userId, 'user_id');

        return $this->database->transaction(function () use (
            $ownerId, $scopedOrganizationId, $actorId, $fromStatus, $toStatus, $action
        ): array {
            $owner = $this->repository->findForUpdate($ownerId, $scopedOrganizationId);

            if ($owner === null) {
                throw new ValidationException(['owner' => 'Owner was not found.']);
            }

            if (($owner['status'] ?? null) !== $fromStatus) {
                throw new ValidationException(['status' => sprintf(
                    'Owner must be %s before it can become %s.',
                    $fromStatus,
                    $toStatus
                )]);
            }

            $timestamp = date('Y-m-d H:i:s');
            $updated = $toStatus === 'inactive'
                ? $this->repository->deactivate($ownerId, $scopedOrganizationId, $timestamp, $actorId)
                : $this->repository->reactivate($ownerId, $scopedOrganizationId, $actorId);

            if ($updated === null) {
                throw new RuntimeException('Owner lifecycle persistence failed.');
            }

            $this->historyRepository->appendOwnerEvent([
                'ulid' => $this->ulidGenerator->generate(),
                'organization_id' => $scopedOrganizationId,
                'owner_id' => $ownerId,
                'action' => $action,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'created_by_user_id' => $actorId,
                'created_at' => $timestamp,
            ]);

            return $updated;
        });
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function normalizeEditable(array $data): array
    {
        $normalized = [];

        foreach (self::EDITABLE_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            $normalized[$field] = is_string($value) ? trim($value) : $value;
            if (in_array($field, ['mobile', 'email', 'preferred_contact_method', 'contact_person_name',
                'contact_person_mobile', 'contact_person_email'], true)
                && $normalized[$field] === '') {
                $normalized[$field] = null;
            }
        }

        if (array_key_exists('mobile', $normalized)) {
            $normalized['mobile_normalized'] = $normalized['mobile'];
        }

        if (array_key_exists('email', $normalized)) {
            $normalized['email_normalized'] = $normalized['email'] === null
                ? null
                : strtolower((string) $normalized['email']);
        }

        return $normalized;
    }

    /** @param array<string, mixed> $data */
    private function validateOwner(array $data): void
    {
        $errors = [];

        if (! isset($data['party_type']) || ! in_array($data['party_type'], self::PARTY_TYPES, true)) {
            $errors['party_type'] = 'Party type must be individual or legal_entity.';
        }

        if (! isset($data['display_name']) || ! is_string($data['display_name']) || trim($data['display_name']) === '') {
            $errors['display_name'] = 'Display name is required.';
        }

        if (($data['preferred_contact_method'] ?? null) !== null
            && ! in_array($data['preferred_contact_method'], self::CONTACT_METHODS, true)) {
            $errors['preferred_contact_method'] = 'Preferred contact method must be phone, whatsapp, or email.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
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
