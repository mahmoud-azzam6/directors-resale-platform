<?php

declare(strict_types=1);

namespace App\Modules\Organization\Models;

/**
 * Represents the root organization business entity.
 */
final class Organization
{
    public function __construct(
        private ?int $id,
        private string $name,
        private string $code,
        private string $organizationType,
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
            (string) ($attributes['name'] ?? ''),
            (string) ($attributes['code'] ?? ''),
            (string) ($attributes['organization_type'] ?? ''),
            (string) ($attributes['status'] ?? ''),
            isset($attributes['created_at']) ? (string) $attributes['created_at'] : null,
            isset($attributes['updated_at']) ? (string) $attributes['updated_at'] : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'organization_type' => $this->organizationType,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function persistenceData(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'organization_type' => $this->organizationType,
            'status' => $this->status,
        ];
    }
}
