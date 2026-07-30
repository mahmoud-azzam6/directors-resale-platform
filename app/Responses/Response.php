<?php

declare(strict_types=1);

namespace App\Responses;

final class Response
{
    /**
     * @var array<string, mixed>
     */
    private array $payload;

    private int $statusCode;

    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(array $payload, int $statusCode)
    {
        $this->payload = $payload;
        $this->statusCode = $statusCode;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    public static function success(
        string $message = '',
        array $data = [],
        array $meta = [],
        int $statusCode = 200
    ): self {
        return new self([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $statusCode);
    }

    public static function error(string $code, string $message, int $statusCode = 400): self
    {
        return new self([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $statusCode);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function json(array $payload, int $statusCode = 200): self
    {
        return new self($payload, $statusCode);
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        if (! headers_sent()) {
            header('Content-Type: application/json');
        }

        echo json_encode($this->payload, JSON_UNESCAPED_SLASHES);
    }
}
