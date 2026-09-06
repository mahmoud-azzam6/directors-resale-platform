<?php

declare(strict_types=1);

namespace App\Modules\GlobalPropertyIdentity\Services;

use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\GlobalPropertyIdentity\Repositories\GlobalPhysicalIdentityLinkRepository;
use App\Modules\GlobalPropertyIdentity\Repositories\GlobalPhysicalPropertyIdentityRepository;
use App\Modules\Property\Repositories\OrganizationPropertyRepository;
use RuntimeException;

final class GlobalPhysicalIdentityService
{
    public function __construct(
        private GlobalPhysicalPropertyIdentityRepository $identityRepository,
        private GlobalPhysicalIdentityLinkRepository $linkRepository,
        private OrganizationPropertyRepository $propertyRepository,
        private DatabaseConnectionInterface $database,
        private UlidGeneratorInterface $ulidGenerator
    ) {
    }

    /** @return array<string, mixed> */
    public function create(int|string $userId): array
    {
        $actorId = $this->positiveIdentifier($userId, 'user_id');

        return $this->identityRepository->create([
            'ulid' => $this->ulidGenerator->generate(),
            'created_by_user_id' => $actorId,
            'updated_by_user_id' => $actorId,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function find(int|string $identityId): ?array
    {
        return $this->identityRepository->find(
            $this->positiveIdentifier($identityId, 'global_physical_property_identity_id')
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->identityRepository->all();
    }

    /** @return array<string, mixed> */
    public function link(
        int|string $propertyId,
        int|string $organizationId,
        int|string $identityId,
        int|string $userId
    ): array {
        $property = $this->positiveIdentifier($propertyId, 'organization_property_id');
        $organization = $this->positiveIdentifier($organizationId, 'organization_id');
        $identity = $this->positiveIdentifier($identityId, 'global_physical_property_identity_id');
        $actor = $this->positiveIdentifier($userId, 'user_id');

        return $this->database->transaction(function () use ($property, $organization, $identity, $actor): array {
            $this->requireLockedProperty($property, $organization);
            $this->requireIdentity($identity);
            if ($this->linkRepository->findActiveForPropertyForUpdate($property) !== null) {
                throw new ValidationException([
                    'global_identity_link' => 'An active Global Physical Identity link already exists for this Property.',
                ]);
            }

            return $this->createLink($property, $identity, $actor);
        });
    }

    /** @return array<string, mixed> */
    public function unlink(
        int|string $propertyId,
        int|string $organizationId,
        string $reason,
        int|string $userId
    ): array {
        $property = $this->positiveIdentifier($propertyId, 'organization_property_id');
        $organization = $this->positiveIdentifier($organizationId, 'organization_id');
        $actor = $this->positiveIdentifier($userId, 'user_id');
        $unlinkReason = $this->requiredReason($reason);

        return $this->database->transaction(function () use ($property, $organization, $actor, $unlinkReason): array {
            $this->requireLockedProperty($property, $organization);
            $active = $this->requireActiveLink($property);
            $timestamp = date('Y-m-d H:i:s');

            return $this->endLink($active, $property, $timestamp, $actor, $unlinkReason);
        });
    }

    /** @return array<string, mixed> */
    public function relink(
        int|string $propertyId,
        int|string $organizationId,
        int|string $newIdentityId,
        string $reason,
        int|string $userId
    ): array {
        $property = $this->positiveIdentifier($propertyId, 'organization_property_id');
        $organization = $this->positiveIdentifier($organizationId, 'organization_id');
        $identity = $this->positiveIdentifier($newIdentityId, 'global_physical_property_identity_id');
        $actor = $this->positiveIdentifier($userId, 'user_id');
        $unlinkReason = $this->requiredReason($reason);

        return $this->database->transaction(function () use (
            $property, $organization, $identity, $actor, $unlinkReason
        ): array {
            $this->requireLockedProperty($property, $organization);
            $active = $this->requireActiveLink($property);
            if ((int) $active['global_physical_property_identity_id'] === $identity) {
                throw new ValidationException([
                    'global_physical_property_identity_id'
                        => 'Property is already linked to this Global Physical Property Identity.',
                ]);
            }
            $this->requireIdentity($identity);
            $timestamp = date('Y-m-d H:i:s');
            $this->endLink($active, $property, $timestamp, $actor, $unlinkReason);

            return $this->createLink($property, $identity, $actor);
        });
    }

    /** @return array<string, mixed>|null */
    public function activeLinkForProperty(int|string $propertyId, int|string $organizationId): ?array
    {
        [$property, $organization] = $this->validatedPropertyScope($propertyId, $organizationId);
        $this->requireProperty($property, $organization);

        return $this->linkRepository->findActiveForProperty($property);
    }

    /** @return array<int, array<string, mixed>> */
    public function linkHistoryForProperty(int|string $propertyId, int|string $organizationId): array
    {
        [$property, $organization] = $this->validatedPropertyScope($propertyId, $organizationId);
        $this->requireProperty($property, $organization);

        return $this->linkRepository->historyForProperty($property);
    }

    /** @return array<int, array<string, mixed>> */
    public function representationsForIdentity(int|string $identityId): array
    {
        $identity = $this->positiveIdentifier($identityId, 'global_physical_property_identity_id');
        $this->requireIdentity($identity);

        return $this->linkRepository->representationsForIdentity($identity);
    }

    /** @return array<string, mixed> */
    private function createLink(int $propertyId, int $identityId, int $actorId): array
    {
        return $this->linkRepository->create([
            'ulid' => $this->ulidGenerator->generate(),
            'organization_property_id' => $propertyId,
            'global_physical_property_identity_id' => $identityId,
            'link_method' => 'manual',
            'unlinked_at' => null,
            'unlinked_by_user_id' => null,
            'unlink_reason' => null,
            'created_by_user_id' => $actorId,
            'updated_by_user_id' => $actorId,
        ]);
    }

    /** @param array<string, mixed> $active @return array<string, mixed> */
    private function endLink(
        array $active,
        int $propertyId,
        string $timestamp,
        int $actorId,
        string $reason
    ): array {
        $ended = $this->linkRepository->unlink((int) $active['id'], $propertyId, $timestamp, $actorId, $reason);
        if ($ended === null
            || (int) ($ended['id'] ?? 0) !== (int) $active['id']
            || (int) ($ended['organization_property_id'] ?? 0) !== $propertyId
            || ($ended['unlinked_at'] ?? null) !== $timestamp
            || (int) ($ended['unlinked_by_user_id'] ?? 0) !== $actorId
            || ($ended['unlink_reason'] ?? null) !== $reason) {
            throw new RuntimeException('Global Physical Identity unlink persistence failed.');
        }

        return $ended;
    }

    /** @return array<string, mixed> */
    private function requireActiveLink(int $propertyId): array
    {
        $active = $this->linkRepository->findActiveForPropertyForUpdate($propertyId);
        if ($active === null) {
            throw new ValidationException([
                'global_identity_link' => 'No active Global Physical Identity link exists for this Property.',
            ]);
        }

        return $active;
    }

    /** @return array<string, mixed> */
    private function requireIdentity(int $identityId): array
    {
        $identity = $this->identityRepository->find($identityId);
        if ($identity === null) {
            throw new ValidationException([
                'global_physical_property_identity' => 'Global Physical Property Identity was not found.',
            ]);
        }

        return $identity;
    }

    /** @return array<string, mixed> */
    private function requireLockedProperty(int $propertyId, int $organizationId): array
    {
        $property = $this->propertyRepository->findForUpdate($propertyId, $organizationId);
        if ($property === null) {
            throw new ValidationException(['organization_property' => 'Organization Property was not found.']);
        }

        return $property;
    }

    /** @return array<string, mixed> */
    private function requireProperty(int $propertyId, int $organizationId): array
    {
        $property = $this->propertyRepository->findInOrganization($propertyId, $organizationId);
        if ($property === null) {
            throw new ValidationException(['organization_property' => 'Organization Property was not found.']);
        }

        return $property;
    }

    /** @return array{0: int, 1: int} */
    private function validatedPropertyScope(int|string $propertyId, int|string $organizationId): array
    {
        return [
            $this->positiveIdentifier($propertyId, 'organization_property_id'),
            $this->positiveIdentifier($organizationId, 'organization_id'),
        ];
    }

    private function requiredReason(string $reason): string
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new ValidationException(['unlink_reason' => 'Unlink reason is required.']);
        }

        return $reason;
    }

    private function positiveIdentifier(mixed $value, string $field): int
    {
        if ((! is_int($value) && (! is_string($value) || ! ctype_digit($value))) || (int) $value <= 0) {
            throw new ValidationException([
                $field => str_replace('_', ' ', ucfirst($field)) . ' must be a valid identifier.',
            ]);
        }

        return (int) $value;
    }
}
