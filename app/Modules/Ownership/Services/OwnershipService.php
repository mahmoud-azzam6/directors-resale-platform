<?php

declare(strict_types=1);

namespace App\Modules\Ownership\Services;

use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Modules\Owner\Repositories\OwnerRepository;
use App\Modules\Ownership\Repositories\AuthorizedActingOwnerDesignationRepository;
use App\Modules\Ownership\Repositories\OwnershipPartyRepository;
use App\Modules\Ownership\Repositories\OwnershipRepository;
use App\Modules\Property\Repositories\OrganizationPropertyRepository;
use RuntimeException;

final class OwnershipService
{
    private const SHARE_SCALE = 10000;
    private const FULL_SHARE = 1000000;

    public function __construct(
        private OwnershipRepository $ownershipRepository,
        private OwnershipPartyRepository $partyRepository,
        private AuthorizedActingOwnerDesignationRepository $designationRepository,
        private OrganizationPropertyRepository $propertyRepository,
        private OwnerRepository $ownerRepository,
        private DatabaseConnectionInterface $database,
        private UlidGeneratorInterface $ulidGenerator
    ) {
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function recordCurrent(array $data, int|string $userId): array
    {
        $propertyId = $this->positiveIdentifier($data['organization_property_id'] ?? null, 'organization_property_id');
        $organizationId = $this->positiveIdentifier($data['organization_id'] ?? null, 'organization_id');
        $actorId = $this->positiveIdentifier($userId, 'user_id');
        $parties = $this->normalizeProposedParties($data['parties'] ?? null);
        $this->validateShareSet(array_column($parties, 'share_percentage'));
        $actingOwner = $this->normalizeInitialActingOwner($data['acting_owner'] ?? null, $parties);

        return $this->database->transaction(function () use (
            $propertyId, $organizationId, $actorId, $parties, $actingOwner
        ): array {
            if ($this->propertyRepository->findForUpdate($propertyId, $organizationId) === null) {
                throw new ValidationException(['organization_property' => 'Organization Property was not found.']);
            }

            if ($this->ownershipRepository->currentExistsForProperty($propertyId, $organizationId)) {
                throw new ValidationException(['ownership' => 'A current Ownership already exists for this Property.']);
            }

            $ownerIds = array_column($parties, 'owner_id');
            sort($ownerIds, SORT_NUMERIC);
            foreach ($ownerIds as $ownerId) {
                if ($this->ownerRepository->findForUpdate($ownerId, $organizationId) === null) {
                    throw new ValidationException(['owner' => 'Owner was not found in the Organization.']);
                }
            }

            $ownership = $this->ownershipRepository->create([
                'ulid' => $this->ulidGenerator->generate(),
                'organization_property_id' => $propertyId,
                'organization_id' => $organizationId,
                'status' => 'current',
                'closed_at' => null,
                'closed_by_user_id' => null,
                'created_by_user_id' => $actorId,
                'updated_by_user_id' => $actorId,
            ]);

            $createdParties = [];
            foreach ($parties as $party) {
                $createdParties[] = $this->partyRepository->create([
                    'ulid' => $this->ulidGenerator->generate(),
                    'ownership_id' => (int) $ownership['id'],
                    'owner_id' => $party['owner_id'],
                    'organization_id' => $organizationId,
                    'share_percentage' => $party['share_percentage'],
                    'created_by_user_id' => $actorId,
                    'updated_by_user_id' => $actorId,
                ]);
            }

            $designation = null;
            if ($actingOwner !== null) {
                $actingParty = $this->partyByOwner($createdParties, $actingOwner['owner_id']);
                if ($actingParty === null) {
                    throw new RuntimeException('Created Acting Owner Party could not be resolved.');
                }
                $designation = $this->createDesignation(
                    (int) $ownership['id'], (int) $actingParty['id'], $actingOwner, $actorId, date('Y-m-d H:i:s')
                );
            }

            return $this->aggregate($ownership, $createdParties, $designation);
        });
    }

    /** @return array<string, mixed>|null */
    public function find(int|string $id, int|string $organizationId): ?array
    {
        return $this->ownershipRepository->findInOrganization(
            $this->positiveIdentifier($id, 'ownership_id'),
            $this->positiveIdentifier($organizationId, 'organization_id')
        );
    }

    /** @return array<string, mixed>|null */
    public function currentForProperty(int|string $propertyId, int|string $organizationId): ?array
    {
        return $this->ownershipRepository->findCurrentForProperty(
            $this->positiveIdentifier($propertyId, 'organization_property_id'),
            $this->positiveIdentifier($organizationId, 'organization_id')
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function historyForProperty(int|string $propertyId, int|string $organizationId): array
    {
        return $this->ownershipRepository->historyForProperty(
            $this->positiveIdentifier($propertyId, 'organization_property_id'),
            $this->positiveIdentifier($organizationId, 'organization_id')
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function parties(int|string $ownershipId, int|string $organizationId): array
    {
        [$id, $scope] = $this->validatedOwnershipScope($ownershipId, $organizationId);
        if ($this->ownershipRepository->findInOrganization($id, $scope) === null) {
            throw new ValidationException(['ownership' => 'Ownership was not found.']);
        }
        return $this->partyRepository->forOwnership($id, $scope);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function addParty(
        int|string $ownershipId,
        int|string $organizationId,
        array $data,
        int|string $userId
    ): array {
        [$id, $scope] = $this->validatedOwnershipScope($ownershipId, $organizationId);
        $actorId = $this->positiveIdentifier($userId, 'user_id');
        $ownerId = $this->positiveIdentifier($data['owner_id'] ?? null, 'owner_id');
        $share = $this->normalizeShare($data['share_percentage'] ?? null);

        return $this->database->transaction(function () use ($id, $scope, $actorId, $ownerId, $share): array {
            $this->requireCurrentOwnership($id, $scope);
            $parties = $this->partyRepository->forOwnership($id, $scope);
            if ($this->ownerRepository->findForUpdate($ownerId, $scope) === null) {
                throw new ValidationException(['owner' => 'Owner was not found in the Organization.']);
            }
            if ($this->partyByOwner($parties, $ownerId) !== null) {
                throw new ValidationException(['owner_id' => 'Owner is already a Party of this Ownership.']);
            }
            $this->validateShareSet(array_merge(array_column($parties, 'share_percentage'), [$share]));

            return $this->partyRepository->create([
                'ulid' => $this->ulidGenerator->generate(),
                'ownership_id' => $id,
                'owner_id' => $ownerId,
                'organization_id' => $scope,
                'share_percentage' => $share,
                'created_by_user_id' => $actorId,
                'updated_by_user_id' => $actorId,
            ]);
        });
    }

    /** @return array<string, mixed> */
    public function updatePartyShare(
        int|string $partyId,
        int|string $ownershipId,
        int|string $organizationId,
        mixed $sharePercentage,
        int|string $userId
    ): array {
        $scopedPartyId = $this->positiveIdentifier($partyId, 'ownership_party_id');
        [$id, $scope] = $this->validatedOwnershipScope($ownershipId, $organizationId);
        $actorId = $this->positiveIdentifier($userId, 'user_id');
        $share = $this->normalizeShare($sharePercentage);

        return $this->database->transaction(function () use ($scopedPartyId, $id, $scope, $actorId, $share): array {
            $this->requireCurrentOwnership($id, $scope);
            $parties = $this->partyRepository->forOwnership($id, $scope);
            if ($this->partyById($parties, $scopedPartyId) === null) {
                throw new ValidationException(['ownership_party' => 'Ownership Party was not found.']);
            }
            $shares = array_map(
                static fn (array $party): mixed => (int) $party['id'] === $scopedPartyId
                    ? $share
                    : ($party['share_percentage'] ?? null),
                $parties
            );
            $this->validateShareSet($shares);
            $updated = $this->partyRepository->updateShare($scopedPartyId, $id, $scope, $share, $actorId);
            if ($updated === null) {
                throw new RuntimeException('Ownership Party share persistence failed.');
            }
            return $updated;
        });
    }

    public function removeParty(
        int|string $partyId,
        int|string $ownershipId,
        int|string $organizationId,
        int|string $userId
    ): void {
        $scopedPartyId = $this->positiveIdentifier($partyId, 'ownership_party_id');
        [$id, $scope] = $this->validatedOwnershipScope($ownershipId, $organizationId);
        $this->positiveIdentifier($userId, 'user_id');

        $this->database->transaction(function () use ($scopedPartyId, $id, $scope): void {
            $this->requireCurrentOwnership($id, $scope);
            $parties = $this->partyRepository->forOwnership($id, $scope);
            if ($this->partyById($parties, $scopedPartyId) === null) {
                throw new ValidationException(['ownership_party' => 'Ownership Party was not found.']);
            }
            if (count($parties) === 1) {
                throw new ValidationException(['ownership_party' => 'Ownership must retain at least one Party.']);
            }
            $designation = $this->designationRepository->findCurrentForUpdate($id);
            if ($designation !== null && (int) $designation['ownership_party_id'] === $scopedPartyId) {
                throw new ValidationException([
                    'ownership_party' => 'The current Acting Owner Party must be cleared or changed before removal.',
                ]);
            }
            $remaining = array_values(array_filter(
                $parties,
                static fn (array $party): bool => (int) $party['id'] !== $scopedPartyId
            ));
            $this->validateShareSet(array_column($remaining, 'share_percentage'));
            if (! $this->partyRepository->remove($scopedPartyId, $id, $scope)) {
                throw new RuntimeException('Ownership Party removal failed.');
            }
        });
    }

    /** @return array<string, mixed> */
    public function close(int|string $ownershipId, int|string $organizationId, int|string $userId): array
    {
        [$id, $scope] = $this->validatedOwnershipScope($ownershipId, $organizationId);
        $actorId = $this->positiveIdentifier($userId, 'user_id');

        return $this->database->transaction(function () use ($id, $scope, $actorId): array {
            $this->requireCurrentOwnership($id, $scope);
            $designation = $this->designationRepository->findCurrentForUpdate($id);
            $timestamp = date('Y-m-d H:i:s');
            if ($designation !== null) {
                $this->requireEndedDesignation($designation, $id, $timestamp, $actorId);
            }
            $closed = $this->ownershipRepository->closeCurrent($id, $scope, $timestamp, $actorId);
            if ($closed === null || ($closed['status'] ?? null) !== 'closed') {
                throw new RuntimeException('Ownership close persistence failed.');
            }
            return $closed;
        });
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function designateActingOwner(
        int|string $ownershipId,
        int|string $organizationId,
        array $data,
        int|string $userId
    ): array {
        return $this->mutateDesignation($ownershipId, $organizationId, $data, $userId, false);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function changeActingOwner(
        int|string $ownershipId,
        int|string $organizationId,
        array $data,
        int|string $userId
    ): array {
        return $this->mutateDesignation($ownershipId, $organizationId, $data, $userId, true);
    }

    public function clearActingOwner(
        int|string $ownershipId,
        int|string $organizationId,
        int|string $userId
    ): void {
        [$id, $scope] = $this->validatedOwnershipScope($ownershipId, $organizationId);
        $actorId = $this->positiveIdentifier($userId, 'user_id');

        $this->database->transaction(function () use ($id, $scope, $actorId): void {
            $this->requireCurrentOwnership($id, $scope);
            $designation = $this->designationRepository->findCurrentForUpdate($id);
            if ($designation === null) {
                throw new ValidationException(['acting_owner' => 'No current Acting Owner designation exists.']);
            }
            $this->requireEndedDesignation($designation, $id, date('Y-m-d H:i:s'), $actorId);
        });
    }

    /** @return array<string, mixed>|null */
    public function currentActingOwner(int|string $ownershipId, int|string $organizationId): ?array
    {
        [$id, $scope] = $this->validatedOwnershipScope($ownershipId, $organizationId);
        if ($this->ownershipRepository->findInOrganization($id, $scope) === null) {
            throw new ValidationException(['ownership' => 'Ownership was not found.']);
        }
        return $this->designationRepository->findCurrent($id);
    }

    /** @return array<int, array<string, mixed>> */
    public function actingOwnerHistory(int|string $ownershipId, int|string $organizationId): array
    {
        [$id, $scope] = $this->validatedOwnershipScope($ownershipId, $organizationId);
        if ($this->ownershipRepository->findInOrganization($id, $scope) === null) {
            throw new ValidationException(['ownership' => 'Ownership was not found.']);
        }
        return $this->designationRepository->historyForOwnership($id);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function mutateDesignation(
        int|string $ownershipId,
        int|string $organizationId,
        array $data,
        int|string $userId,
        bool $replace
    ): array {
        [$id, $scope] = $this->validatedOwnershipScope($ownershipId, $organizationId);
        $actorId = $this->positiveIdentifier($userId, 'user_id');
        $attributes = $this->normalizeDesignation($data);

        return $this->database->transaction(function () use ($id, $scope, $actorId, $attributes, $replace): array {
            $this->requireCurrentOwnership($id, $scope);
            if ($this->partyRepository->find($attributes['ownership_party_id'], $id, $scope) === null) {
                throw new ValidationException(['ownership_party_id' => 'Acting Owner Party must belong to the Ownership.']);
            }
            $current = $this->designationRepository->findCurrentForUpdate($id);
            if (! $replace && $current !== null) {
                throw new ValidationException(['acting_owner' => 'A current Acting Owner designation already exists.']);
            }
            if ($replace && $current === null) {
                throw new ValidationException(['acting_owner' => 'No current Acting Owner designation exists.']);
            }
            $timestamp = date('Y-m-d H:i:s');
            if ($replace) {
                $this->requireEndedDesignation($current, $id, $timestamp, $actorId);
            }
            return $this->createDesignation(
                $id, $attributes['ownership_party_id'], $attributes, $actorId, $timestamp
            );
        });
    }

    /** @return array<string, mixed> */
    private function requireCurrentOwnership(int $ownershipId, int $organizationId): array
    {
        $ownership = $this->ownershipRepository->findForUpdate($ownershipId, $organizationId);
        if ($ownership === null) {
            throw new ValidationException(['ownership' => 'Ownership was not found.']);
        }
        if (($ownership['status'] ?? null) !== 'current') {
            throw new ValidationException(['status' => 'Ownership must be current for this operation.']);
        }
        return $ownership;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function normalizeDesignation(array $data): array
    {
        $partyId = $this->positiveIdentifier($data['ownership_party_id'] ?? null, 'ownership_party_id');
        $basis = $data['basis_source'] ?? null;
        if (! is_string($basis) || trim($basis) === '' || strlen(trim($basis)) > 255) {
            throw new ValidationException(['basis_source' => 'Basis source is required and must not exceed 255 characters.']);
        }
        $notes = $data['notes'] ?? null;
        if ($notes !== null && ! is_string($notes)) {
            throw new ValidationException(['notes' => 'Notes must be a string or null.']);
        }
        $notes = $notes === null || trim($notes) === '' ? null : trim($notes);
        return ['ownership_party_id' => $partyId, 'basis_source' => trim($basis), 'notes' => $notes];
    }

    /** @param mixed $value @return array<string, mixed>|null */
    private function normalizeInitialActingOwner(mixed $value, array $parties): ?array
    {
        if ($value === null) {
            return null;
        }
        if (! is_array($value)) {
            throw new ValidationException(['acting_owner' => 'Acting Owner must be an object or null.']);
        }
        $ownerId = $this->positiveIdentifier($value['owner_id'] ?? null, 'owner_id');
        if ($this->partyByOwner($parties, $ownerId) === null) {
            throw new ValidationException(['acting_owner' => 'Acting Owner must be one of the proposed Ownership Parties.']);
        }
        $designation = $this->normalizeDesignation([
            'ownership_party_id' => 1,
            'basis_source' => $value['basis_source'] ?? null,
            'notes' => $value['notes'] ?? null,
        ]);
        $designation['owner_id'] = $ownerId;
        unset($designation['ownership_party_id']);
        return $designation;
    }

    /** @param mixed $value @return array<int, array{owner_id: int, share_percentage: string|null}> */
    private function normalizeProposedParties(mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            throw new ValidationException(['parties' => 'At least one Ownership Party is required.']);
        }
        $parties = [];
        $seen = [];
        foreach ($value as $party) {
            if (! is_array($party)) {
                throw new ValidationException(['parties' => 'Each Ownership Party must be an object.']);
            }
            $ownerId = $this->positiveIdentifier($party['owner_id'] ?? null, 'owner_id');
            if (isset($seen[$ownerId])) {
                throw new ValidationException(['owner_id' => 'Owner may appear only once in an Ownership.']);
            }
            $seen[$ownerId] = true;
            $parties[] = [
                'owner_id' => $ownerId,
                'share_percentage' => $this->normalizeShare($party['share_percentage'] ?? null),
            ];
        }
        return $parties;
    }

    private function normalizeShare(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value)) {
            $raw = (string) $value;
        } elseif (is_string($value) && preg_match('/^(?:0|[1-9][0-9]*)(?:\.[0-9]{1,4})?$/', $value) === 1) {
            $raw = $value;
        } else {
            throw new ValidationException(['share_percentage' => 'Share percentage must be a valid decimal with up to four places.']);
        }
        $scaled = $this->shareToScaled($raw);
        if ($scaled <= 0 || $scaled > self::FULL_SHARE) {
            throw new ValidationException(['share_percentage' => 'Share percentage must be greater than zero and no greater than 100.']);
        }
        return intdiv($scaled, self::SHARE_SCALE) . '.' . str_pad(
            (string) ($scaled % self::SHARE_SCALE), 4, '0', STR_PAD_LEFT
        );
    }

    /** @param array<int, mixed> $shares */
    private function validateShareSet(array $shares): void
    {
        $total = 0;
        $hasUnknown = false;
        foreach ($shares as $share) {
            $normalized = $this->normalizeShare($share);
            if ($normalized === null) {
                $hasUnknown = true;
                continue;
            }
            $total += $this->shareToScaled($normalized);
        }
        if ($total > self::FULL_SHARE) {
            throw new ValidationException(['share_percentage' => 'Known Ownership shares must not exceed 100%.']);
        }
        if (! $hasUnknown && $total !== self::FULL_SHARE) {
            throw new ValidationException(['share_percentage' => 'Fully known Ownership shares must total exactly 100%.']);
        }
    }

    private function shareToScaled(string $share): int
    {
        [$whole, $fraction] = array_pad(explode('.', $share, 2), 2, '');
        return ((int) $whole * self::SHARE_SCALE) + (int) str_pad($fraction, 4, '0');
    }

    /** @param array<int, array<string, mixed>> $parties @return array<string, mixed>|null */
    private function partyByOwner(array $parties, int $ownerId): ?array
    {
        foreach ($parties as $party) {
            if ((int) $party['owner_id'] === $ownerId) {
                return $party;
            }
        }
        return null;
    }

    /** @param array<int, array<string, mixed>> $parties @return array<string, mixed>|null */
    private function partyById(array $parties, int $partyId): ?array
    {
        foreach ($parties as $party) {
            if ((int) $party['id'] === $partyId) {
                return $party;
            }
        }
        return null;
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    private function createDesignation(
        int $ownershipId,
        int $partyId,
        array $attributes,
        int $actorId,
        string $timestamp
    ): array {
        return $this->designationRepository->create([
            'ulid' => $this->ulidGenerator->generate(),
            'ownership_id' => $ownershipId,
            'ownership_party_id' => $partyId,
            'basis_source' => $attributes['basis_source'],
            'notes' => $attributes['notes'],
            'started_at' => $timestamp,
            'ended_at' => null,
            'ended_by_user_id' => null,
            'created_by_user_id' => $actorId,
            'updated_by_user_id' => $actorId,
        ]);
    }

    /** @param array<string, mixed> $designation */
    private function requireEndedDesignation(
        array $designation,
        int $ownershipId,
        string $timestamp,
        int $actorId
    ): void {
        $ended = $this->designationRepository->end(
            (int) $designation['id'], $ownershipId, $timestamp, $actorId
        );
        if ($ended === null || ($ended['ended_at'] ?? null) === null) {
            throw new RuntimeException('Acting Owner designation end persistence failed.');
        }
    }

    /** @return array{0: int, 1: int} */
    private function validatedOwnershipScope(int|string $ownershipId, int|string $organizationId): array
    {
        return [
            $this->positiveIdentifier($ownershipId, 'ownership_id'),
            $this->positiveIdentifier($organizationId, 'organization_id'),
        ];
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

    /** @param array<string, mixed> $ownership @param array<int, array<string, mixed>> $parties @param array<string, mixed>|null $designation @return array<string, mixed> */
    private function aggregate(array $ownership, array $parties, ?array $designation): array
    {
        $ownership['parties'] = $parties;
        $ownership['acting_owner'] = $designation;
        return $ownership;
    }
}
