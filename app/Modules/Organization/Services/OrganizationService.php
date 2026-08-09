<?php

declare(strict_types=1);

namespace App\Modules\Organization\Services;

use App\Exceptions\ValidationException;
use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Repositories\OrganizationRepository;
use App\Modules\Organization\Validators\OrganizationValidator;

/**
 * Coordinates organization validation and persistence operations.
 */
final class OrganizationService
{
    public function __construct(
        private OrganizationRepository $repository,
        private OrganizationValidator $validator
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return array_map(
            static fn (array $record): array => Organization::fromArray($record)->toArray(),
            $this->repository->all()
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array
    {
        $record = $this->repository->find($id);

        return $record === null ? null : Organization::fromArray($record)->toArray();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $data = $this->normalize($data);
        $this->ensureValid($this->validator->validate($data));

        $organization = Organization::fromArray($data);

        return Organization::fromArray(
            $this->repository->create($organization->persistenceData())
        )->toArray();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function update(int|string $id, array $data): ?array
    {
        $existing = $this->repository->find($id);

        if ($existing === null) {
            return null;
        }

        $data = $this->normalize(array_merge($existing, $data));
        $this->ensureValid($this->validator->validateForUpdate($data, $id));

        $organization = Organization::fromArray($data);
        $updated = $this->repository->update($id, $organization->persistenceData());

        return $updated === null ? null : Organization::fromArray($updated)->toArray();
    }

    public function delete(int|string $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * @param array<string, mixed> $errors
     */
    private function ensureValid(array $errors): void
    {
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $normalized = [];

        foreach (['name', 'code', 'organization_type', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $normalized[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            }
        }

        return $normalized;
    }
}
