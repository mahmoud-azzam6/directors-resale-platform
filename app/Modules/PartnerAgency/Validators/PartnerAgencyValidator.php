<?php

declare(strict_types=1);

namespace App\Modules\PartnerAgency\Validators;

use App\Core\Contracts\ValidatorInterface;
use App\Modules\PartnerAgency\Repositories\PartnerAgencyRepository;

/**
 * Validates Partner Agency input and Franchise parent relationships.
 */
final class PartnerAgencyValidator implements ValidatorInterface
{
    /** @var array<string, string> */
    private array $validationErrors = [];

    public function __construct(private PartnerAgencyRepository $repository)
    {
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    public function validate(array $data): array
    {
        return $this->validateData($data);
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    public function validateForUpdate(array $data, int|string $partnerAgencyId): array
    {
        return $this->validateData($data, $partnerAgencyId);
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'code' => 'required|unique',
            'organization_type' => 'partner_agency',
            'parent_organization_id' => 'required|franchise',
            'status' => 'required',
        ];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->validationErrors;
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    private function validateData(array $data, int|string|null $partnerAgencyId = null): array
    {
        $errors = [];

        foreach (['name', 'code', 'status'] as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = sprintf('%s is required.', str_replace('_', ' ', ucfirst($field)));
            }
        }

        if (($data['organization_type'] ?? null) !== 'partner_agency') {
            $errors['organization_type'] = 'Organization type must be partner_agency.';
        }

        if (isset($data['code']) && is_string($data['code']) && trim($data['code']) !== ''
            && $this->repository->codeExists(trim($data['code']), $partnerAgencyId)) {
            $errors['code'] = 'Code must be unique.';
        }

        $parentId = $data['parent_organization_id'] ?? null;

        if (! is_int($parentId) && (! is_string($parentId) || ctype_digit($parentId) === false)) {
            $errors['parent_organization_id'] = 'Parent organization is required.';
        } elseif ((int) $parentId <= 0) {
            $errors['parent_organization_id'] = 'Parent organization is required.';
        } elseif ($partnerAgencyId !== null && (int) $parentId === (int) $partnerAgencyId) {
            $errors['parent_organization_id'] = 'A Partner Agency cannot be its own parent.';
        } else {
            $parent = $this->repository->findParent((int) $parentId);

            if ($parent === null) {
                $errors['parent_organization_id'] = 'Parent organization must exist.';
            } elseif (($parent['organization_type'] ?? null) !== 'franchise') {
                $errors['parent_organization_id'] = 'Parent organization must be a Franchise.';
            }
        }

        return $this->validationErrors = $errors;
    }
}
