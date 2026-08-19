<?php

declare(strict_types=1);

namespace App\Modules\User\Validators;

use App\Core\Contracts\ValidatorInterface;
use App\Modules\User\Repositories\UserRepository;

/**
 * Validates reusable User input rules.
 */
final class UserValidator implements ValidatorInterface
{
    /** @var array<string, string> */
    private array $validationErrors = [];

    public function __construct(private UserRepository $repository)
    {
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    public function validate(array $data): array
    {
        return $this->validateData($data);
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    public function validateForUpdate(array $data, int|string $userId): array
    {
        return $this->validateData($data, $userId);
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'full_name' => 'required',
            'email' => 'required|email|unique',
            'organization_id' => 'required|integer',
            'status' => 'required|active,inactive',
        ];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->validationErrors;
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    private function validateData(array $data, int|string|null $ignoreId = null): array
    {
        $errors = [];

        foreach (['full_name', 'email', 'status'] as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = sprintf('%s is required.', str_replace('_', ' ', ucfirst($field)));
            }
        }

        $organizationId = $data['organization_id'] ?? null;
        if (! is_int($organizationId) && (! is_string($organizationId) || ctype_digit($organizationId) === false)) {
            $errors['organization_id'] = 'Organization is required and must be an integer.';
        } elseif ((int) $organizationId <= 0) {
            $errors['organization_id'] = 'Organization is required and must be an integer.';
        }

        if (isset($data['email']) && is_string($data['email']) && trim($data['email']) !== '') {
            if (filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL) === false) {
                $errors['email'] = 'Email must be valid.';
            } elseif ($this->repository->emailExists(trim($data['email']), $ignoreId)) {
                $errors['email'] = 'Email must be unique.';
            }
        }

        if (isset($data['status']) && is_string($data['status'])
            && ! in_array(trim($data['status']), ['active', 'inactive'], true)) {
            $errors['status'] = 'Status must be active or inactive.';
        }

        return $this->validationErrors = $errors;
    }
}