<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Creates HTTP request abstractions from the native PHP request environment.
 */
final class Kernel
{
    public function createRequest(): Request
    {
        $server = $_SERVER;

        return new Request(
            new InputBag($_GET),
            new InputBag($_POST),
            new InputBag($this->jsonInput($server)),
            new HeaderBag($this->headers($server)),
            new InputBag($_COOKIE),
            new InputBag($server),
            $this->files($_FILES),
            (string) ($server['REQUEST_METHOD'] ?? 'GET'),
            (string) ($server['REQUEST_URI'] ?? '/')
        );
    }

    /**
     * @param array<string, mixed> $server
     * @return array<string, mixed>
     */
    private function jsonInput(array $server): array
    {
        $contentType = (string) ($server['CONTENT_TYPE'] ?? '');

        if (strtolower(trim(explode(';', $contentType, 2)[0])) !== 'application/json') {
            return [];
        }

        $input = json_decode((string) file_get_contents('php://input'), true);

        return is_array($input) ? $input : [];
    }

    /**
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private function headers(array $server): array
    {
        $headers = [];

        foreach ($server as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $headers[str_replace('_', '-', substr($name, 5))] = (string) $value;

                continue;
            }

            if (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $headers[str_replace('_', '-', $name)] = (string) $value;
            }
        }

        if (isset($server['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['AUTHORIZATION'] = (string) $server['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[$name] = (string) $value;
            }
        }

        return $headers;
    }

    /**
     * @param array<string, mixed> $files
     * @return array<string, UploadedFile|array<int|string, UploadedFile|array>>
     */
    private function files(array $files): array
    {
        $uploadedFiles = [];

        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $uploadedFiles[$key] = $this->normalizeFile($file);
            }
        }

        return $uploadedFiles;
    }

    /**
     * @param array<string, mixed> $file
     * @return UploadedFile|array<int|string, UploadedFile|array>
     */
    private function normalizeFile(array $file): UploadedFile|array
    {
        $name = $file['name'] ?? '';

        if (! is_array($name)) {
            return new UploadedFile(
                (string) $name,
                (string) ($file['type'] ?? ''),
                (string) ($file['tmp_name'] ?? ''),
                (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE),
                (int) ($file['size'] ?? 0)
            );
        }

        $files = [];

        foreach ($name as $key => $nestedName) {
            $files[$key] = $this->normalizeFile([
                'name' => $nestedName,
                'type' => $file['type'][$key] ?? '',
                'tmp_name' => $file['tmp_name'][$key] ?? '',
                'error' => $file['error'][$key] ?? UPLOAD_ERR_NO_FILE,
                'size' => $file['size'][$key] ?? 0,
            ]);
        }

        return $files;
    }
}
