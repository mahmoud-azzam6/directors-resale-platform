<?php

declare(strict_types=1);

namespace App\Modules\GlobalPropertyIdentity\Repositories;

use App\Core\Database\QueryBuilderInterface;
use RuntimeException;

final class GlobalPhysicalPropertyIdentityRepository
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    public function create(array $attributes): array
    {
        $id = $this->queryBuilder->table('global_physical_property_identities')->insert($attributes);
        $record = $this->find($id);
        if ($record === null) {
            throw new RuntimeException('Created Global Physical Property Identity could not be retrieved.');
        }
        return $record;
    }

    /** @return array<string, mixed>|null */
    public function find(int|string $id): ?array
    {
        return $this->queryBuilder->table('global_physical_property_identities')->where('id', '=', $id)->first();
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->queryBuilder->table('global_physical_property_identities')->orderBy('id')->get();
    }
}
