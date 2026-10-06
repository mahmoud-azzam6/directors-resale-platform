<?php

declare(strict_types=1);

namespace App\Modules\Listing\Repositories;

use App\Core\Database\DatabaseConnectionInterface;
use PDO;

final class ListingRepository
{
    public function __construct(private DatabaseConnectionInterface $database) {}

    public function find(int $id, bool $lock = false): ?array
    {
        return $this->one('SELECT * FROM listings WHERE id=?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
    }

    public function allInScope(array $organizations): array
    {
        if ($organizations === []) { return []; }
        $statement = $this->database->connection()->prepare('SELECT * FROM listings WHERE organization_id IN (' . implode(',', array_fill(0, count($organizations), '?')) . ') ORDER BY id DESC');
        $statement->execute($organizations);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeForProperty(int $property): ?array
    {
        return $this->one('SELECT * FROM listings WHERE active_property_guard=?', [$property]);
    }

    public function create(string $ulid, int $property, int $organization, int $actor): array
    {
        $statement = $this->database->connection()->prepare('INSERT INTO listings (ulid,organization_property_id,organization_id,created_by_user_id,updated_by_user_id) VALUES (?,?,?,?,?)');
        $statement->execute([$ulid, $property, $organization, $actor, $actor]);
        return $this->find((int) $this->database->connection()->lastInsertId());
    }

    public function transition(int $id, string $status, int $actor): array
    {
        $timestamp = $status === 'published' ? 'published_at' : 'archived_at';
        $statement = $this->database->connection()->prepare("UPDATE listings SET status=?,revision=revision+1,{$timestamp}=CURRENT_TIMESTAMP,updated_by_user_id=? WHERE id=?");
        $statement->execute([$status, $actor, $id]);
        return $this->find($id);
    }

    public function profile(int $property, int $organization): ?array
    {
        return $this->one('SELECT p.geographic_location_id,p.address_text,p.initial_asking_price,p.currency_code FROM organization_property_profiles p JOIN organization_properties r ON r.id=p.organization_property_id WHERE p.organization_property_id=? AND r.organization_id=?', [$property, $organization]);
    }

    public function location(int $id): ?array
    {
        return $this->one('SELECT id,name_ar,name_en,type AS location_type FROM geographic_locations WHERE id=?', [$id]);
    }

    public function validOwnership(int $property, int $organization): bool
    {
        return $this->one("SELECT o.id FROM ownerships o JOIN ownership_parties p ON p.ownership_id=o.id AND p.organization_id=o.organization_id JOIN owners r ON r.id=p.owner_id AND r.organization_id=p.organization_id WHERE o.organization_property_id=? AND o.organization_id=? AND o.status='current' AND r.status='active' LIMIT 1", [$property, $organization]) !== null;
    }

    private function one(string $sql, array $values): ?array
    {
        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($values);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
