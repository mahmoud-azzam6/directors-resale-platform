<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Framework-independent representation of an HTTP request.
 */
final class Request
{
    private InputBag $input;

    /**
     * @param array<string, UploadedFile|array<int|string, UploadedFile|array>> $files
     */
    public function __construct(
        private InputBag $query,
        private InputBag $form,
        private InputBag $json,
        private HeaderBag $headers,
        private InputBag $cookies,
        private InputBag $server,
        private array $files,
        private string $method,
        private string $uri
    ) {
        $this->input = new InputBag(array_replace(
            $this->query->all(),
            $this->form->all(),
            $this->json->all()
        ));
        $this->method = strtoupper($this->method);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->input->get($key, $default);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query->get($key, $default);
    }

    public function json(string $key, mixed $default = null): mixed
    {
        return $this->json->get($key, $default);
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers->get($name, $default);
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies->get($key, $default);
    }

    public function file(string $key): UploadedFile|array|null
    {
        return $this->files[$key] ?? null;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server->get($key, $default);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function contentType(): ?string
    {
        $contentType = $this->header('Content-Type');

        if ($contentType === null) {
            return null;
        }

        return strtolower(trim(explode(';', $contentType, 2)[0]));
    }

    public function has(string $key): bool
    {
        return $this->input->has($key);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->input->all();
    }

    /**
     * @return array<string, UploadedFile|array<int|string, UploadedFile|array>>
     */
    public function files(): array
    {
        return $this->files;
    }
}
