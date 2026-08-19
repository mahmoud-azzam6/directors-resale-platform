<?php

declare(strict_types=1);

namespace App\Modules\User\Models;

/**
 * Represents the staged User business entity.
 */
final class User
{
    public function __construct(
        private ?int $id,
        private int $organizationId,
        private string $fullName,
        private string $email,
        private ?string $phone,
        private string $status,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            isset($attributes['id']) ? (int) $attributes['id'] : null,
            (int) ($attributes['organization_id'] ?? 0),
            (string) ($attributes['full_name'] ?? ''),
            (string) ($attributes['email'] ?? ''),
            isset($attributes['phone']) ? (string) $attributes['phone'] : null,
            (string) ($attributes['status'] ?? ''),
            isset($attributes['created_at']) ? (string) $attributes['created_at'] : null,
            isset($attributes['updated_at']) ? (string) $attributes['updated_at'] : null
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /** @return array<string, mixed> */
    public function persistenceData(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
        ];
    }
}