<?php

declare(strict_types=1);

namespace App\Modules\Franchise\Validators;

use App\Core\Contracts\ValidatorInterface;
use App\Modules\Franchise\Repositories\FranchiseRepository;

/**
 * Validates Franchise input and System Organization parent relationships.
 */
final class FranchiseValidator implements ValidatorInterface
{
    /**
     * @var array<string, string>
     */
    private array $validationErrors = [];

    public function __construct(private FranchiseRepository $repository)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public function validate(array $data): array
    {
        return $this->validateData($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public function validateForUpdate(array $data, int|string $franchiseId): array
    {
        return $this->validateData($data, $franchiseId);
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'code' => 'required|unique',
            'organization_type' => 'franchise',
            'parent_organization_id' => 'required|system_organization',
            'status' => 'required',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->validationErrors;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function validateData(array $data, int|string|null $franchiseId = null): array
    {
        $errors = [];

        foreach (['name', 'code', 'status'] as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = sprintf('%s is required.', str_replace('_', ' ', ucfirst($field)));
            }
        }

        if (($data['organization_type'] ?? null) !== 'franchise') {
            $errors['organization_type'] = 'Organization type must be franchise.';
        }

        if (isset($data['code']) && is_string($data['code']) && trim($data['code']) !== ''
            && $this->repository->codeExists(trim($data['code']), $franchiseId)) {
            $errors['code'] = 'Code must be unique.';
        }

        $parentId = $data['parent_organization_id'] ?? null;

        if (! is_int($parentId) && (! is_string($parentId) || ctype_digit($parentId) === false)) {
            $errors['parent_organization_id'] = 'Parent organization is required.';
        } elseif ((int) $parentId <= 0) {
            $errors['parent_organization_id'] = 'Parent organization is required.';
        } elseif ($franchiseId !== null && (int) $parentId === (int) $franchiseId) {
            $errors['parent_organization_id'] = 'A Franchise cannot be its own parent.';
        } else {
            $parent = $this->repository->findParent((int) $parentId);

            if ($parent === null) {
                $errors['parent_organization_id'] = 'Parent organization must exist.';
            } elseif (($parent['organization_type'] ?? null) !== 'system') {
                $errors['parent_organization_id'] = 'Parent organization must be a System Organization.';
            }
        }

        return $this->validationErrors = $errors;
    }
}
