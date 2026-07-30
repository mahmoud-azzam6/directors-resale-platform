<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Defines validation for structured input data.
 */
interface ValidatorInterface
{
    /**
     * Validate data against this validator's rules.
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public function validate(array $data): array;

    /**
     * Return the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array;

    /**
     * Return validation errors.
     *
     * @return array<string, string>
     */
    public function errors(): array;
}
