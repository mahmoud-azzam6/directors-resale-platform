<?php

declare(strict_types=1);

use App\Core\Database\BaseQueryBuilder;
use App\Core\Database\DatabaseConnectionInterface;
use App\Modules\GlobalPropertyIdentity\Repositories\GlobalPhysicalIdentityLinkRepository;
use App\Modules\GlobalPropertyIdentity\Repositories\GlobalPhysicalPropertyIdentityRepository;
use App\Modules\Owner\Repositories\OwnerRepository;
use App\Modules\Ownership\Repositories\AuthorizedActingOwnerDesignationRepository;
use App\Modules\Ownership\Repositories\OwnershipPartyRepository;
use App\Modules\Ownership\Repositories\OwnershipRepository;
use App\Modules\Property\Repositories\OrganizationPropertyRepository;
use App\Modules\Property\Repositories\PropertyOwnerLifecycleHistoryRepository;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

final class RepositorySpyStatement extends PDOStatement
{
    /** @param array<int, array<string, mixed>> $rows */
    public function __construct(private array $rows)
    {
    }

    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        return true;
    }

    public function execute(?array $params = null): bool
    {
        return true;
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        return $this->rows;
    }

    public function rowCount(): int
    {
        return 1;
    }
}

final class RepositorySpyPdo extends PDO
{
    /** @var array<int, string> */
    public array $sql = [];

    /** @var array<int, array<string, mixed>> */
    public array $rows = [['id' => 1, 'organization_id' => 10, 'ownership_id' => 20]];

    public function __construct()
    {
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->sql[] = $query;

        return new RepositorySpyStatement($this->rows);
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return '1';
    }
}

final class RepositorySpyConnection implements DatabaseConnectionInterface
{
    public function __construct(private RepositorySpyPdo $pdo)
    {
    }

    public function connection(): PDO
    {
        return $this->pdo;
    }

    public function beginTransaction(): void
    {
    }

    public function commit(): void
    {
    }

    public function rollback(): void
    {
    }

    public function transaction(callable $callback): mixed
    {
        return $callback($this);
    }
}

function repositoryAssertContains(string $expected, string $actual, string $message): void
{
    if (! str_contains($actual, $expected)) {
        throw new RuntimeException($message . "\nSQL: " . $actual);
    }
}

function repositoryAssertNotContains(string $unexpected, string $actual, string $message): void
{
    if (str_contains($actual, $unexpected)) {
        throw new RuntimeException($message . "\nSQL: " . $actual);
    }
}

function repositoryAssertSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

function lastRepositorySql(RepositorySpyPdo $pdo): string
{
    return $pdo->sql[array_key_last($pdo->sql)];
}

$pdo = new RepositorySpyPdo();
$connection = new RepositorySpyConnection($pdo);
$builder = static fn (): BaseQueryBuilder => new BaseQueryBuilder($connection);

$properties = new OrganizationPropertyRepository($builder());
$property = $properties->create([
    'ulid' => str_repeat('P', 26), 'organization_id' => 10, 'property_label' => 'Unit 1',
    'status' => 'active', 'created_by_user_id' => 1, 'updated_by_user_id' => 1,
]);
repositoryAssertSame(1, $property['id'], 'Organization Property create/read failed.');
$properties->allInScope([10, 11]);
repositoryAssertContains('`organization_id` IN', lastRepositorySql($pdo), 'Property list is not Organization-scoped.');
$properties->findForUpdate(1, 10);
repositoryAssertContains('FOR UPDATE', lastRepositorySql($pdo), 'Property locking path is missing.');
repositoryAssertSame([], $properties->allInScope([]), 'Empty Property scope must return no records.');
$propertyUpdateIndex = count($pdo->sql);
$properties->updateStoredFields(1, 10, [
    'property_label' => 'Updated Unit', 'updated_by_user_id' => 1,
    'status' => 'archived', 'archived_at' => '2026-09-05 10:00:00', 'archived_by_user_id' => 1,
]);
$propertyUpdateSql = $pdo->sql[$propertyUpdateIndex];
repositoryAssertContains('`property_label` =', $propertyUpdateSql, 'Property generic update omitted an editable field.');
repositoryAssertNotContains('`status` =', $propertyUpdateSql, 'Property generic update permits status.');
repositoryAssertNotContains('`archived_at` =', $propertyUpdateSql, 'Property generic update permits archived_at.');
repositoryAssertNotContains('`archived_by_user_id` =', $propertyUpdateSql, 'Property generic update permits archived_by_user_id.');
$properties->archive(1, 10, '2026-09-05 10:00:00', 1);
repositoryAssertContains('SELECT * FROM `organization_properties`', lastRepositorySql($pdo), 'Property archive persistence failed.');
$properties->reactivate(1, 10, 1);
repositoryAssertContains('SELECT * FROM `organization_properties`', lastRepositorySql($pdo), 'Property reactivation persistence failed.');

$owners = new OwnerRepository($builder());
$owner = $owners->create([
    'ulid' => str_repeat('O', 26), 'organization_id' => 10, 'party_type' => 'individual',
    'display_name' => 'Owner One', 'status' => 'active', 'created_by_user_id' => 1, 'updated_by_user_id' => 1,
]);
repositoryAssertSame(1, $owner['id'], 'Owner create/read failed.');
$owners->findByMobileNormalized(10, '201000000000');
repositoryAssertContains('`organization_id` =', lastRepositorySql($pdo), 'Mobile search lacks Organization isolation.');
$owners->findByEmailNormalized(11, 'owner@example.com');
$emailSql = lastRepositorySql($pdo);
repositoryAssertContains('`organization_id` =', $emailSql, 'Email search lacks Organization isolation.');
repositoryAssertContains('`email_normalized` =', $emailSql, 'Email search does not use the normalized field.');
$owners->searchByDisplayName(10, 'Owner');
repositoryAssertContains('`display_name` LIKE', lastRepositorySql($pdo), 'Display-name search is missing.');
$owners->findForUpdate(1, 10);
repositoryAssertContains('FOR UPDATE', lastRepositorySql($pdo), 'Owner locking path is missing.');
$ownerUpdateIndex = count($pdo->sql);
$owners->updateStoredFields(1, 10, [
    'display_name' => 'Updated Owner', 'updated_by_user_id' => 1,
    'status' => 'inactive', 'deactivated_at' => '2026-09-05 10:00:00', 'deactivated_by_user_id' => 1,
]);
$ownerUpdateSql = $pdo->sql[$ownerUpdateIndex];
repositoryAssertContains('`display_name` =', $ownerUpdateSql, 'Owner generic update omitted an editable field.');
repositoryAssertNotContains('`status` =', $ownerUpdateSql, 'Owner generic update permits status.');
repositoryAssertNotContains('`deactivated_at` =', $ownerUpdateSql, 'Owner generic update permits deactivated_at.');
repositoryAssertNotContains('`deactivated_by_user_id` =', $ownerUpdateSql, 'Owner generic update permits deactivated_by_user_id.');
$owners->deactivate(1, 10, '2026-09-05 10:00:00', 1);
repositoryAssertContains('SELECT * FROM `owners`', lastRepositorySql($pdo), 'Owner deactivation persistence failed.');
$owners->reactivate(1, 10, 1);
repositoryAssertContains('SELECT * FROM `owners`', lastRepositorySql($pdo), 'Owner reactivation persistence failed.');

$ownerships = new OwnershipRepository($builder());
$ownership = $ownerships->create([
    'ulid' => str_repeat('W', 26), 'organization_property_id' => 1, 'organization_id' => 10,
    'status' => 'current', 'created_by_user_id' => 1, 'updated_by_user_id' => 1,
]);
repositoryAssertSame(1, $ownership['id'], 'Ownership create/read failed.');
$ownerships->findCurrentForProperty(1, 10);
repositoryAssertContains('`status` =', lastRepositorySql($pdo), 'Current Ownership lookup lacks status filtering.');
$ownerships->historyForProperty(1, 10);
repositoryAssertContains('ORDER BY `created_at` DESC, `id` DESC', lastRepositorySql($pdo), 'Ownership history order is invalid.');
$ownerships->findCurrentForPropertyForUpdate(1, 10);
repositoryAssertContains('FOR UPDATE', lastRepositorySql($pdo), 'Current Ownership locking path is missing.');
$ownerships->findForUpdate(1, 10);
repositoryAssertContains('FOR UPDATE', lastRepositorySql($pdo), 'Ownership locking path is missing.');
$ownerships->closeCurrent(1, 10, '2026-09-05 10:00:00', 1);
repositoryAssertContains('SELECT * FROM `ownerships`', lastRepositorySql($pdo), 'Ownership close persistence failed.');

$parties = new OwnershipPartyRepository($builder());
$party = $parties->create([
    'ulid' => str_repeat('R', 26), 'ownership_id' => 20, 'owner_id' => 1,
    'organization_id' => 10, 'created_by_user_id' => 1, 'updated_by_user_id' => 1,
]);
repositoryAssertSame(1, $party['id'], 'Ownership Party create/read failed.');
$parties->forOwnership(20, 10);
repositoryAssertContains('`organization_id` =', lastRepositorySql($pdo), 'Ownership Party list lacks Organization isolation.');
repositoryAssertSame(true, $parties->ownerExists(20, 1, 10), 'Ownership Party existence lookup failed.');
$parties->updateShare(1, 20, 10, '50.0000', 1);
repositoryAssertContains('SELECT * FROM `ownership_parties`', lastRepositorySql($pdo), 'Ownership Party share update failed.');
$parties->remove(1, 20, 10);
repositoryAssertContains('DELETE FROM `ownership_parties`', lastRepositorySql($pdo), 'Ownership Party removal failed.');

$designations = new AuthorizedActingOwnerDesignationRepository($builder());
$designation = $designations->create([
    'ulid' => str_repeat('D', 26), 'ownership_id' => 20, 'ownership_party_id' => 1,
    'basis_source' => 'authorization', 'started_at' => '2026-09-05 09:00:00',
    'created_by_user_id' => 1, 'updated_by_user_id' => 1,
]);
repositoryAssertSame(1, $designation['id'], 'Acting Owner designation create/read failed.');
$designations->findCurrent(20);
repositoryAssertContains('`current_ownership_guard` =', lastRepositorySql($pdo), 'Current designation lookup is invalid.');
$designations->historyForOwnership(20);
repositoryAssertContains('ORDER BY `started_at` DESC, `id` DESC', lastRepositorySql($pdo), 'Designation history order is invalid.');
$designations->findCurrentForUpdate(20);
repositoryAssertContains('FOR UPDATE', lastRepositorySql($pdo), 'Designation locking path is missing.');
$designationEndIndex = count($pdo->sql);
$designations->end(1, 20, '2026-09-05 10:00:00', 1);
$designationEndSql = $pdo->sql[$designationEndIndex];
repositoryAssertContains('UPDATE `authorized_acting_owner_designations`', $designationEndSql, 'Designation end must update first.');
repositoryAssertContains('`id` =', $designationEndSql, 'Designation end does not constrain the exact designation.');
repositoryAssertContains('`ownership_id` =', $designationEndSql, 'Designation end does not constrain Ownership.');
repositoryAssertContains('`current_ownership_guard` =', $designationEndSql, 'Designation end does not preserve the current guard.');
repositoryAssertSame(2, count($pdo->sql) - $designationEndIndex, 'Designation end performed unexpected record discovery.');
repositoryAssertContains('SELECT * FROM `authorized_acting_owner_designations`', lastRepositorySql($pdo), 'Designation end persistence failed.');

$identities = new GlobalPhysicalPropertyIdentityRepository($builder());
$identity = $identities->create([
    'ulid' => str_repeat('G', 26), 'created_by_user_id' => 1, 'updated_by_user_id' => 1,
]);
repositoryAssertSame(1, $identity['id'], 'Global Identity create/read failed.');
$identities->all();
repositoryAssertContains('ORDER BY `id` ASC', lastRepositorySql($pdo), 'Global Identity list is invalid.');

$links = new GlobalPhysicalIdentityLinkRepository($builder());
$link = $links->create([
    'ulid' => str_repeat('L', 26), 'organization_property_id' => 1,
    'global_physical_property_identity_id' => 1, 'link_method' => 'manual',
    'created_by_user_id' => 1, 'updated_by_user_id' => 1,
]);
repositoryAssertSame(1, $link['id'], 'Global Identity link create/read failed.');
$links->findActiveForProperty(1);
repositoryAssertContains('`active_property_guard` =', lastRepositorySql($pdo), 'Active Global link lookup is invalid.');
$links->historyForProperty(1);
repositoryAssertContains('ORDER BY `created_at` DESC, `id` DESC', lastRepositorySql($pdo), 'Global link history order is invalid.');
$links->representationsForIdentity(1);
repositoryAssertContains('`global_physical_property_identity_id` =', lastRepositorySql($pdo), 'Identity representations lookup is invalid.');
$links->findActiveForPropertyForUpdate(1);
repositoryAssertContains('FOR UPDATE', lastRepositorySql($pdo), 'Global link locking path is missing.');
$unlinkIndex = count($pdo->sql);
$links->unlink(1, 1, '2026-09-05 10:00:00', 1, 'Incorrect match');
$unlinkSql = $pdo->sql[$unlinkIndex];
repositoryAssertContains('UPDATE `global_physical_identity_links`', $unlinkSql, 'Global unlink must update first.');
repositoryAssertContains('`id` =', $unlinkSql, 'Global unlink does not constrain the exact link.');
repositoryAssertContains('`organization_property_id` =', $unlinkSql, 'Global unlink does not constrain the Property.');
repositoryAssertContains('`active_property_guard` =', $unlinkSql, 'Global unlink does not preserve the active guard.');
repositoryAssertSame(2, count($pdo->sql) - $unlinkIndex, 'Global unlink performed unexpected active-link discovery.');
repositoryAssertContains('SELECT * FROM `global_physical_identity_links`', lastRepositorySql($pdo), 'Global unlink persistence failed.');

$history = new PropertyOwnerLifecycleHistoryRepository($builder());
$event = $history->appendPropertyEvent([
    'ulid' => str_repeat('H', 26), 'organization_id' => 10, 'organization_property_id' => 1,
    'action' => 'property_archive', 'from_status' => 'active', 'to_status' => 'archived',
    'created_by_user_id' => 1,
]);
repositoryAssertSame(1, $event['id'], 'Property lifecycle append failed.');
$history->forProperty(1, 10);
repositoryAssertContains('`organization_id` =', lastRepositorySql($pdo), 'Property history lacks Organization isolation.');
$history->forOwner(1, 10);
repositoryAssertContains('`owner_id` =', lastRepositorySql($pdo), 'Owner history retrieval is invalid.');
$ownerEvent = $history->appendOwnerEvent([
    'ulid' => str_repeat('E', 26), 'organization_id' => 10, 'owner_id' => 1,
    'action' => 'owner_deactivate', 'from_status' => 'active', 'to_status' => 'inactive',
    'created_by_user_id' => 1,
]);
repositoryAssertSame(1, $ownerEvent['id'], 'Owner lifecycle append failed.');
repositoryAssertSame(false, method_exists($history, 'update'), 'Lifecycle History must not expose update.');
repositoryAssertSame(false, method_exists($history, 'delete'), 'Lifecycle History must not expose delete.');

echo "BF013 repository persistence tests passed.\n";
