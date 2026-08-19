<?php

declare(strict_types=1);

namespace App\Modules\Permission\Models;

final class Permission
{
    public function __construct(
        private ?int $id,
        private string $code,
        private string $name,
        private string $status,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {
    }

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self(
            isset($attributes['id']) ? (int) $attributes['id'] : null,
            (string) ($attributes['code'] ?? ''),
            (string) ($attributes['name'] ?? ''),
            (string) ($attributes['status'] ?? ''),
            isset($attributes['created_at']) ? (string) $attributes['created_at'] : null,
            isset($attributes['updated_at']) ? (string) $attributes['updated_at'] : null
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'status' => $this->status,
            'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}