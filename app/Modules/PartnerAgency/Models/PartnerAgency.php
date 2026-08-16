<?php

declare(strict_types=1);

namespace App\Modules\PartnerAgency\Models;

/**
 * Represents an Organization-backed Partner Agency record.
 */
final class PartnerAgency
{
    public function __construct(
        private ?int $id,
        private string $name,
        private string $code,
        private int $parentOrganizationId,
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
            isset($attributes['parent_organization_id']) ? (int) $attributes['parent_organization_id'] : 0,
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
            'organization_type' => 'partner_agency',
            'parent_organization_id' => $this->parentOrganizationId,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * @return array<string, int|string>
     */
    public function persistenceData(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'organization_type' => 'partner_agency',
            'parent_organization_id' => $this->parentOrganizationId,
            'status' => $this->status,
        ];
    }
}
