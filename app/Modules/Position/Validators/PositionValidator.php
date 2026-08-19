<?php

declare(strict_types=1);

namespace App\Modules\Position\Validators;

use App\Core\Contracts\ValidatorInterface;
use App\Modules\Position\Repositories\PositionRepository;

/**
 * Validates reusable Position input rules.
 */
final class PositionValidator implements ValidatorInterface
{
    /** @var array<string, string> */
    private array $validationErrors = [];

    public function __construct(private PositionRepository $repository)
    {
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    public function validate(array $data): array
    {
        return $this->validateData($data);
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    public function validateForUpdate(array $data, int|string $positionId): array
    {
        return $this->validateData($data, $positionId);
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'code' => 'required|unique within organization',
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

        foreach (['name', 'code', 'status'] as $field) {
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

        if (isset($data['status']) && is_string($data['status'])
            && ! in_array(trim($data['status']), ['active', 'inactive'], true)) {
            $errors['status'] = 'Status must be active or inactive.';
        }

        if (isset($data['code'], $data['organization_id'])
            && is_string($data['code']) && trim($data['code']) !== ''
            && (is_int($organizationId) || (is_string($organizationId) && ctype_digit($organizationId)))) {
            if ($this->repository->codeExists((int) $organizationId, trim($data['code']), $ignoreId)) {
                $errors['code'] = 'Code must be unique within the Organization.';
            }
        }

        return $this->validationErrors = $errors;
    }
}