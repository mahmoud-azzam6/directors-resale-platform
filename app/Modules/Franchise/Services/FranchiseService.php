<?php

declare(strict_types=1);

namespace App\Modules\Franchise\Services;

use App\Exceptions\ValidationException;
use App\Modules\Franchise\Models\Franchise;
use App\Modules\Franchise\Repositories\FranchiseRepository;
use App\Modules\Franchise\Validators\FranchiseValidator;

/**
 * Coordinates Franchise validation, Organization hierarchy rules, and persistence.
 */
final class FranchiseService
{
    public function __construct(
        private FranchiseRepository $repository,
        private FranchiseValidator $validator
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return array_map(
            static fn (array $record): array => Franchise::fromArray($record)->toArray(),
            $this->repository->allActive()
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array
    {
        $record = $this->repository->findActive($id);

        return $record === null ? null : Franchise::fromArray($record)->toArray();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $data = $this->normalize($data);
        $this->ensureValid($this->validator->validate($data));

        $franchise = Franchise::fromArray($data);

        return Franchise::fromArray(
            $this->repository->create($franchise->persistenceData())
        )->toArray();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function update(int|string $id, array $data): ?array
    {
        $existing = $this->repository->findActive($id);

        if ($existing === null) {
            return null;
        }

        $data = $this->normalize(array_merge($existing, $data));
        $this->ensureValid($this->validator->validateForUpdate($data, $id));

        $franchise = Franchise::fromArray($data);
        $updated = $this->repository->update($id, $franchise->persistenceData());

        return $updated === null ? null : Franchise::fromArray($updated)->toArray();
    }

    public function archive(int|string $id): bool
    {
        if ($this->repository->findActive($id) === null) {
            return false;
        }

        return $this->repository->archive($id) !== null;
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
        $normalized = [
            'organization_type' => 'franchise',
        ];

        foreach (['name', 'code', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $normalized[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            }
        }

        if (array_key_exists('parent_organization_id', $data)) {
            $normalized['parent_organization_id'] = $data['parent_organization_id'];
        }

        return $normalized;
    }
}
