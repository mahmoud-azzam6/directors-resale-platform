<?php

declare(strict_types=1);

namespace App\Modules\PartnerAgency\Services;

use App\Exceptions\ValidationException;
use App\Modules\PartnerAgency\Models\PartnerAgency;
use App\Modules\PartnerAgency\Repositories\PartnerAgencyRepository;
use App\Modules\PartnerAgency\Validators\PartnerAgencyValidator;

/**
 * Coordinates Partner Agency validation, hierarchy rules, and persistence.
 */
final class PartnerAgencyService
{
    public function __construct(
        private PartnerAgencyRepository $repository,
        private PartnerAgencyValidator $validator
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return array_map(
            static fn (array $record): array => PartnerAgency::fromArray($record)->toArray(),
            $this->repository->allActive()
        );
    }

    /** @return array<string, mixed>|null */
    public function find(int|string $id): ?array
    {
        $record = $this->repository->findActive($id);

        return $record === null ? null : PartnerAgency::fromArray($record)->toArray();
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function create(array $data): array
    {
        $data = $this->normalize($data);
        $this->ensureValid($this->validator->validate($data));

        $partnerAgency = PartnerAgency::fromArray($data);

        return PartnerAgency::fromArray(
            $this->repository->create($partnerAgency->persistenceData())
        )->toArray();
    }

    /** @param array<string, mixed> $data @return array<string, mixed>|null */
    public function update(int|string $id, array $data): ?array
    {
        $existing = $this->repository->findActive($id);

        if ($existing === null) {
            return null;
        }

        $data = $this->normalize(array_merge($existing, $data));
        $this->ensureValid($this->validator->validateForUpdate($data, $id));

        $partnerAgency = PartnerAgency::fromArray($data);
        $updated = $this->repository->update($id, $partnerAgency->persistenceData());

        return $updated === null ? null : PartnerAgency::fromArray($updated)->toArray();
    }

    public function archive(int|string $id): bool
    {
        if ($this->repository->findActive($id) === null) {
            return false;
        }

        return $this->repository->archive($id) !== null;
    }

    /** @param array<string, mixed> $errors */
    private function ensureValid(array $errors): void
    {
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function normalize(array $data): array
    {
        $normalized = ['organization_type' => 'partner_agency'];

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
