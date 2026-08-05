<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Immutable representation of an uploaded file.
 */
final class UploadedFile
{
    public function __construct(
        private string $name,
        private string $mimeType,
        private string $temporaryPath,
        private int $error,
        private int $size
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function temporaryPath(): string
    {
        return $this->temporaryPath;
    }

    public function error(): int
    {
        return $this->error;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }
}
