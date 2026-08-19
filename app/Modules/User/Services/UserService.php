<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Exceptions\ValidationException;
use App\Modules\Organization\Repositories\OrganizationRepository;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Validators\UserValidator;

/**
 * Coordinates User validation, organization ownership, and persistence.
 */
final class UserService
{
    public function __construct(
        private UserRepository $repository,
        private UserValidator $validator,
        private OrganizationRepository $organizationRepository
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return array_map(
            static fn (array $record): array => User::fromArray($record)->toArray(),
            $this->repository->allActive()
        );
    }

    /** @return array<string, mixed>|null */
    public function find(int|string $id): ?array
    {
        $record = $this->repository->findActive($id);

        return $record === null ? null : User::fromArray($record)->toArray();
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function create(array $data): array
    {
        $data = $this->normalize($data);
        $this->ensureValid($this->validator->validate($data));
        $this->ensureOrganizationIsAllowed((int) $data['organization_id']);

        $user = User::fromArray($data);

        return User::fromArray($this->repository->create($user->persistenceData()))->toArray();
    }

    /** @param array<string, mixed> $data @return array<string, mixed>|null */
    public function update(int|string $id, array $data): ?array
    {
        $existing = $this->repository->findActive($id);

        if ($existing === null) {
            return null;
        }

        $normalizedInput = $this->normalize($data);
        $errors = [];

        if (array_key_exists('organization_id', $normalizedInput)
            && (string) $normalizedInput['organization_id'] !== (string) $existing['organization_id']) {
            $errors['organization_id'] = 'Organization cannot be changed through User update.';
        }

        $merged = $this->normalize(array_merge($existing, $normalizedInput));
        $errors = array_merge($errors, $this->validator->validateForUpdate($merged, $id));
        $this->ensureValid($errors);

        $user = User::fromArray($merged);
        $updated = $this->repository->update($id, $user->persistenceData());

        return $updated === null ? null : User::fromArray($updated)->toArray();
    }

    public function deactivate(int|string $id): bool
    {
        if ($this->repository->findActive($id) === null) {
            return false;
        }

        return $this->repository->deactivate($id) !== null;
    }

    /** @param array<string, string> $errors */
    private function ensureValid(array $errors): void
    {
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    private function ensureOrganizationIsAllowed(int $organizationId): void
    {
        $organization = $this->organizationRepository->find($organizationId);
        $supportedTypes = ['system', 'franchise', 'partner_agency'];

        if ($organization === null) {
            throw new ValidationException(['organization_id' => 'Organization must exist.']);
        }

        if (($organization['status'] ?? null) === 'inactive') {
            throw new ValidationException(['organization_id' => 'Organization must be active.']);
        }

        if (! in_array($organization['organization_type'] ?? null, $supportedTypes, true)) {
            throw new ValidationException(['organization_id' => 'Organization type is not supported for Users.']);
        }
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function normalize(array $data): array
    {
        $normalized = [];

        foreach (['full_name', 'email', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $normalized[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            }
        }

        if (array_key_exists('phone', $data)) {
            $normalized['phone'] = $data['phone'] === null
                ? null
                : (is_string($data['phone']) ? trim($data['phone']) : $data['phone']);
        }

        if (array_key_exists('organization_id', $data)) {
            $normalized['organization_id'] = $data['organization_id'];
        }

        return $normalized;
    }
}