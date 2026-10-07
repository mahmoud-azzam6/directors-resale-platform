<?php

declare(strict_types=1);

namespace App\Modules\Request\Repositories;

use App\Core\Database\DatabaseConnectionInterface;
use PDO;

final class ListingInterestRequestRepository
{
    public function __construct(private DatabaseConnectionInterface $database) {}

    public function published(array $scope): array
    {
        if ($scope === []) { return []; }
        return $this->rows("SELECT l.* FROM listings l JOIN organization_properties p ON p.id=l.organization_property_id AND p.organization_id=l.organization_id JOIN organizations o ON o.id=l.organization_id WHERE l.status='published' AND p.status='active' AND o.status='active' AND o.organization_type IN ('franchise','partner_agency') AND l.organization_id IN (" . implode(',', array_fill(0, count($scope), '?')) . ') ORDER BY l.id DESC', $scope);
    }

    public function own(array $user, array $scope, ?int $id = null): array
    {
        if ($scope === []) { return []; }
        $sql = 'SELECT * FROM listing_interest_requests WHERE requester_user_id=? AND requester_organization_id=? AND listing_organization_id IN (' . implode(',', array_fill(0, count($scope), '?')) . ')';
        $values = [(int) $user['id'], (int) $user['organization_id'], ...$scope];
        if ($id !== null) { $sql .= ' AND id=?'; $values[] = $id; }
        return $this->rows($sql . ' ORDER BY id DESC', $values);
    }

    public function existing(int $listing, int $user, bool $lock = false): ?array
    {
        return $this->rows('SELECT * FROM listing_interest_requests WHERE listing_id=? AND requester_user_id=?' . ($lock ? ' FOR UPDATE' : ''), [$listing, $user])[0] ?? null;
    }

    public function create(string $ulid, array $listing, array $user): array
    {
        $statement = $this->database->connection()->prepare('INSERT INTO listing_interest_requests (ulid,listing_id,organization_property_id,listing_organization_id,requester_user_id,requester_organization_id) VALUES (?,?,?,?,?,?)');
        $statement->execute([$ulid, $listing['id'], $listing['organization_property_id'], $listing['organization_id'], $user['id'], $user['organization_id']]);
        return $this->existing((int) $listing['id'], (int) $user['id']);
    }

    private function rows(string $sql, array $values): array
    {
        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($values);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
