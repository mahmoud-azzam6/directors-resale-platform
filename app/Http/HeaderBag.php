<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Immutable, case-insensitive collection of HTTP headers.
 */
final class HeaderBag
{
    /**
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * @param array<string, string> $headers
     */
    public function __construct(array $headers = [])
    {
        foreach ($headers as $name => $value) {
            $this->headers[$this->normalize($name)] = $value;
        }
    }

    public function get(string $name, ?string $default = null): ?string
    {
        return $this->headers[$this->normalize($name)] ?? $default;
    }

    public function has(string $name): bool
    {
        return array_key_exists($this->normalize($name), $this->headers);
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->headers;
    }

    private function normalize(string $name): string
    {
        return strtolower($name);
    }
}
