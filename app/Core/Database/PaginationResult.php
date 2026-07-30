<?php

declare(strict_types=1);

namespace App\Core\Database;

/**
 * Immutable representation of a single page of results.
 */
final class PaginationResult
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $data;

    private int $total;

    private int $page;

    private int $perPage;

    private int $lastPage;

    /**
     * @param array<int, array<string, mixed>> $data
     */
    public function __construct(array $data, int $total, int $page, int $perPage, int $lastPage)
    {
        $this->data = $data;
        $this->total = $total;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->lastPage = $lastPage;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function lastPage(): int
    {
        return $this->lastPage;
    }
}
