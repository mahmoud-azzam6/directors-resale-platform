<?php

declare(strict_types=1);

namespace App\Modules\Organization\Validators;

use App\Core\Contracts\ValidatorInterface;
use App\Modules\Organization\Repositories\OrganizationRepository;

/**
 * Validates organization input and enforces organization code uniqueness.
 */
final class OrganizationValidator implements ValidatorInterface
{
    /**
     * @var array<string, string>
     */
    private array $validationErrors = [];

    public function __construct(private OrganizationRepository $repository)
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
    public function validateForUpdate(array $data, int|string $organizationId): array
    {
        return $this->validateData($data, $organizationId);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'code' => 'required|unique',
            'organization_type' => 'required',
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
    private function validateData(array $data, int|string|null $ignoreId = null): array
    {
        $errors = [];

        foreach (['name', 'code', 'organization_type', 'status'] as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = sprintf('%s is required.', str_replace('_', ' ', ucfirst($field)));
            }
        }

        if (isset($data['code']) && is_string($data['code']) && trim($data['code']) !== '') {
            if ($this->repository->codeExists(trim($data['code']), $ignoreId)) {
                $errors['code'] = 'Code must be unique.';
            }
        }

        return $this->validationErrors = $errors;
    }
}
