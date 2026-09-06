<?php

declare(strict_types=1);

use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\Database\QueryBuilderInterface;
use App\Exceptions\ValidationException;
use App\Modules\Owner\Repositories\OwnerRepository;
use App\Modules\Ownership\Repositories\AuthorizedActingOwnerDesignationRepository;
use App\Modules\Ownership\Repositories\OwnershipPartyRepository;
use App\Modules\Ownership\Repositories\OwnershipRepository;
use App\Modules\Ownership\Services\OwnershipService;
use App\Modules\Property\Repositories\OrganizationPropertyRepository;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

final class OwnershipTestUlids implements UlidGeneratorInterface
{
    public int $calls = 0;

    public function generate(): string
    {
        return str_pad('OWN' . ++$this->calls, 26, '0');
    }
}

final class OwnershipTestDatabase implements DatabaseConnectionInterface
{
    public array $events = [];
    public ?OwnershipTestQueryBuilder $query = null;

    public function connection(): PDO
    {
        throw new RuntimeException('Direct SQL is not expected in OwnershipService.');
    }

    public function beginTransaction(): void
    {
        $this->events[] = 'transaction:begin';
    }

    public function commit(): void
    {
        $this->events[] = 'transaction:commit';
    }

    public function rollback(): void
    {
        $this->events[] = 'transaction:rollback';
    }

    public function transaction(callable $callback): mixed
    {
        $snapshot = $this->query?->tables;
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($snapshot !== null) {
                $this->query->tables = $snapshot;
            }
            $this->rollback();
            throw $exception;
        }
    }
}

final class OwnershipTestQueryBuilder implements QueryBuilderInterface
{
    /** @var array<string, array<int, array<string, mixed>>> */
    public array $tables;
    public array $operations = [];
    public ?string $failOperation = null;
    private string $tableName = '';
    private array $conditions = [];
    private array $orders = [];
    private bool $locked = false;

    public function __construct(private OwnershipTestDatabase $database)
    {
        $this->tables = [
            'organization_properties' => [
                1 => ['id' => 1, 'organization_id' => 10, 'status' => 'active'],
                2 => ['id' => 2, 'organization_id' => 20, 'status' => 'active'],
            ],
            'owners' => [
                1 => ['id' => 1, 'organization_id' => 10, 'status' => 'active'],
                2 => ['id' => 2, 'organization_id' => 10, 'status' => 'active'],
                3 => ['id' => 3, 'organization_id' => 10, 'status' => 'inactive'],
                4 => ['id' => 4, 'organization_id' => 20, 'status' => 'active'],
            ],
            'ownerships' => [],
            'ownership_parties' => [],
            'authorized_acting_owner_designations' => [],
        ];
        $database->query = $this;
    }

    public function table(string $table): self { $this->tableName = $table; return $this; }
    public function select(array $columns = ['*']): self { return $this; }
    public function where(string $field, string $operator, mixed $value): self
    {
        $this->conditions[] = [$field, $operator, $value]; return $this;
    }
    public function orWhere(string $field, string $operator, mixed $value): self
    {
        return $this->where($field, $operator, $value);
    }
    public function whereIn(string $field, array $values): self
    {
        $this->conditions[] = [$field, 'IN', $values]; return $this;
    }
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->orders[] = [$field, strtoupper($direction)]; return $this;
    }
    public function limit(int $limit): self { return $this; }
    public function offset(int $offset): self { return $this; }
    public function forUpdate(): self { $this->locked = true; return $this; }

    public function first(): ?array
    {
        $rows = $this->matchingRows();
        $this->record('first');
        return $rows[0] ?? null;
    }

    public function get(): array
    {
        $rows = $this->matchingRows();
        $this->record('get');
        return $rows;
    }

    public function insert(array $data): int
    {
        $table = $this->tableName;
        $this->maybeFail('insert:' . $table);
        $id = $this->tables[$table] === [] ? 1 : max(array_keys($this->tables[$table])) + 1;
        $data['id'] = $id;
        if ($table === 'authorized_acting_owner_designations') {
            $data['current_ownership_guard'] = $data['ended_at'] === null ? $data['ownership_id'] : null;
        }
        $this->tables[$table][$id] = $data;
        $this->record('insert', $data);
        return $id;
    }

    public function update(array $data): int
    {
        $table = $this->tableName;
        $this->maybeFail('update:' . $table);
        $count = 0;
        foreach ($this->tables[$table] as $id => $row) {
            if ($this->matches($row)) {
                $this->tables[$table][$id] = array_merge($row, $data);
                if ($table === 'authorized_acting_owner_designations' && ($data['ended_at'] ?? null) !== null) {
                    $this->tables[$table][$id]['current_ownership_guard'] = null;
                }
                $count++;
            }
        }
        $this->record('update', $data);
        return $count;
    }

    public function delete(): int
    {
        $table = $this->tableName;
        $this->maybeFail('delete:' . $table);
        $count = 0;
        foreach ($this->tables[$table] as $id => $row) {
            if ($this->matches($row)) {
                unset($this->tables[$table][$id]);
                $count++;
            }
        }
        $this->record('delete');
        return $count;
    }

    private function matchingRows(): array
    {
        $rows = array_values(array_filter($this->tables[$this->tableName] ?? [], fn (array $row): bool => $this->matches($row)));
        foreach (array_reverse($this->orders) as [$field, $direction]) {
            usort($rows, static fn (array $left, array $right): int => $direction === 'DESC'
                ? ($right[$field] ?? null) <=> ($left[$field] ?? null)
                : ($left[$field] ?? null) <=> ($right[$field] ?? null));
        }
        return $rows;
    }

    private function matches(array $row): bool
    {
        foreach ($this->conditions as [$field, $operator, $value]) {
            $actual = $row[$field] ?? null;
            if ($operator === '=' && $actual != $value) { return false; }
            if ($operator === 'IN' && ! in_array($actual, $value, true)) { return false; }
        }
        return true;
    }

    private function maybeFail(string $operation): void
    {
        if ($this->failOperation === $operation) {
            throw new RuntimeException('Injected persistence failure.');
        }
    }

    private function record(string $type, ?array $data = null): void
    {
        $operation = ['type' => $type, 'table' => $this->tableName, 'lock' => $this->locked, 'conditions' => $this->conditions];
        if ($data !== null) { $operation['data'] = $data; }
        $this->operations[] = $operation;
        $this->database->events[] = $type . ':' . $this->tableName . ($this->locked ? ':lock' : '');
        $this->tableName = '';
        $this->conditions = [];
        $this->orders = [];
        $this->locked = false;
    }
}

function ownershipFixture(): array
{
    $database = new OwnershipTestDatabase();
    $query = new OwnershipTestQueryBuilder($database);
    $ulids = new OwnershipTestUlids();
    $service = new OwnershipService(
        new OwnershipRepository($query),
        new OwnershipPartyRepository($query),
        new AuthorizedActingOwnerDesignationRepository($query),
        new OrganizationPropertyRepository($query),
        new OwnerRepository($query),
        $database,
        $ulids
    );
    return compact('database', 'query', 'ulids', 'service');
}

function ownershipAssert(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
}

function ownershipValidation(callable $callback, string $message): void
{
    try { $callback(); } catch (ValidationException) { return; }
    throw new RuntimeException($message);
}

function recordOwnership(OwnershipService $service, array $shares = ['100'], ?array $actingOwner = null): array
{
    $parties = [];
    foreach ($shares as $index => $share) {
        $parties[] = ['owner_id' => $index + 1, 'share_percentage' => $share];
    }
    return $service->recordCurrent([
        'organization_property_id' => 1,
        'organization_id' => 10,
        'parties' => $parties,
        'acting_owner' => $actingOwner,
    ], 9);
}

$fixture = ownershipFixture();
$aggregate = recordOwnership($fixture['service'], ['50', '50'], [
    'owner_id' => 1, 'basis_source' => 'signed authority', 'notes' => 'Verified',
]);
ownershipAssert($aggregate['status'] === 'current' && count($aggregate['parties']) === 2, 'Current Ownership aggregate was not created.');
ownershipAssert($aggregate['parties'][0]['share_percentage'] === '50.0000', 'Shares must persist canonically.');
ownershipAssert($aggregate['acting_owner']['ownership_party_id'] === 1, 'Initial Acting Owner was not created.');
ownershipAssert($fixture['ulids']->calls === 4, 'Every inserted aggregate record must receive a ULID.');
$events = $fixture['database']->events;
$propertyLock = array_search('first:organization_properties:lock', $events, true);
$ownerLocks = array_keys($events, 'first:owners:lock', true);
ownershipAssert($propertyLock !== false && $ownerLocks !== [] && $propertyLock < $ownerLocks[0], 'Property must lock before Owners.');
$lockedOwnerIds = array_map(static fn (array $operation): int => (int) $operation['conditions'][0][2], array_values(array_filter(
    $fixture['query']->operations,
    static fn (array $operation): bool => $operation['table'] === 'owners' && $operation['lock']
)));
ownershipAssert($lockedOwnerIds === [1, 2], 'Owners must lock in ascending ID order.');
ownershipValidation(fn () => recordOwnership($fixture['service']), 'A second current Ownership must be rejected.');

foreach ([['100'], ['50', '50'], ['60', '40'], ['60', null], [null, null], ['60', '20', null], ['70', '30', null]] as $shares) {
    $fixture = ownershipFixture();
    recordOwnership($fixture['service'], $shares);
}
foreach ([['60', '30'], ['60', '50'], ['70', '40', null], [0, '100'], [-1, '100'], ['101'], ['1.00001', null], [1.5, null], ['1e2']] as $shares) {
    $fixture = ownershipFixture();
    ownershipValidation(fn () => recordOwnership($fixture['service'], $shares), 'Invalid share set was accepted.');
    ownershipAssert($fixture['query']->tables['ownerships'] === [], 'Invalid shares must fail before persistence.');
}

$fixture = ownershipFixture();
ownershipValidation(fn () => $fixture['service']->recordCurrent([
    'organization_property_id' => 1, 'organization_id' => 10, 'parties' => [],
], 9), 'Zero parties must be rejected.');
ownershipValidation(fn () => $fixture['service']->recordCurrent([
    'organization_property_id' => 1, 'organization_id' => 10,
    'parties' => [['owner_id' => 1], ['owner_id' => 1]],
], 9), 'Duplicate Owner IDs must be rejected.');
ownershipValidation(fn () => $fixture['service']->recordCurrent([
    'organization_property_id' => 99, 'organization_id' => 10,
    'parties' => [['owner_id' => 1, 'share_percentage' => 100]],
], 9), 'Missing Property must be rejected.');
ownershipValidation(fn () => $fixture['service']->recordCurrent([
    'organization_property_id' => 1, 'organization_id' => 10,
    'parties' => [['owner_id' => 4, 'share_percentage' => 100]],
], 9), 'Cross-Organization Owner must be rejected.');

$fixture = ownershipFixture();
$fixture['query']->failOperation = 'insert:ownership_parties';
try { recordOwnership($fixture['service']); } catch (RuntimeException) {}
ownershipAssert($fixture['query']->tables['ownerships'] === [], 'Failed creation must roll back Ownership insertion.');
ownershipAssert(in_array('transaction:rollback', $fixture['database']->events, true), 'Failed creation must roll back its transaction.');

$fixture = ownershipFixture();
$ownership = recordOwnership($fixture['service'], ['60', null]);
$fixture['service']->addParty($ownership['id'], 10, ['owner_id' => 3, 'share_percentage' => '20'], 9);
ownershipValidation(fn () => $fixture['service']->addParty($ownership['id'], 10, ['owner_id' => 3], 9), 'Duplicate Party must be rejected.');
$fixture['service']->updatePartyShare(1, $ownership['id'], 10, '50', 9);
ownershipValidation(fn () => $fixture['service']->updatePartyShare(1, $ownership['id'], 10, '90', 9), 'Invalid resulting update must be rejected.');
ownershipValidation(fn () => $fixture['service']->removeParty(2, $ownership['id'], 10, 9), 'Invalid resulting removal must be rejected.');
$fixture['service']->removeParty(3, $ownership['id'], 10, 9);

$fixture = ownershipFixture();
$ownership = recordOwnership($fixture['service'], ['50', '50']);
$designation = $fixture['service']->designateActingOwner($ownership['id'], 10, [
    'ownership_party_id' => 1, 'basis_source' => 'written confirmation', 'notes' => null,
], 9);
$fixture['query']->tables['ownership_parties'][99] = [
    'id' => 99, 'ownership_id' => 99, 'owner_id' => 3, 'organization_id' => 10,
    'share_percentage' => '100.0000',
];
ownershipValidation(fn () => $fixture['service']->changeActingOwner($ownership['id'], 10, [
    'ownership_party_id' => 99, 'basis_source' => 'wrong ownership',
], 9), 'Acting Owner Party from another Ownership must be rejected.');
ownershipValidation(fn () => $fixture['service']->designateActingOwner($ownership['id'], 10, [
    'ownership_party_id' => 2, 'basis_source' => 'replacement',
], 9), 'Designate must reject an existing current designation.');
ownershipValidation(fn () => $fixture['service']->removeParty(1, $ownership['id'], 10, 9), 'Current Acting Owner Party removal must be rejected.');
$replacement = $fixture['service']->changeActingOwner($ownership['id'], 10, [
    'ownership_party_id' => 2, 'basis_source' => 'new authority',
], 9);
$history = $fixture['service']->actingOwnerHistory($ownership['id'], 10);
ownershipAssert(count($history) === 2 && $replacement['id'] !== $designation['id'], 'Acting Owner change must preserve history.');
ownershipAssert($history[1]['ended_at'] === $replacement['started_at'], 'Acting Owner change must use one timestamp.');
$fixture['service']->clearActingOwner($ownership['id'], 10, 9);
ownershipAssert($fixture['service']->currentActingOwner($ownership['id'], 10) === null, 'Clear must end the current designation.');
ownershipValidation(fn () => $fixture['service']->clearActingOwner($ownership['id'], 10, 9), 'Clear without current designation must fail.');
ownershipValidation(fn () => $fixture['service']->changeActingOwner($ownership['id'], 10, [
    'ownership_party_id' => 1, 'basis_source' => 'none',
], 9), 'Change without current designation must fail.');

$fixture = ownershipFixture();
$ownership = recordOwnership($fixture['service'], ['100'], [
    'owner_id' => 1, 'basis_source' => 'authority',
]);
$closed = $fixture['service']->close($ownership['id'], 10, 9);
$history = $fixture['service']->actingOwnerHistory($ownership['id'], 10);
ownershipAssert($closed['status'] === 'closed', 'Ownership must close.');
ownershipAssert($closed['closed_at'] === $history[0]['ended_at'], 'Close must use one timestamp for Ownership and designation.');
ownershipAssert(count($fixture['service']->parties($ownership['id'], 10)) === 1, 'Close must preserve Parties.');
ownershipValidation(fn () => $fixture['service']->close($ownership['id'], 10, 9), 'Repeated close must fail.');
ownershipValidation(fn () => $fixture['service']->addParty($ownership['id'], 10, ['owner_id' => 2], 9), 'Closed Ownership must reject Party mutation.');
ownershipValidation(fn () => $fixture['service']->updatePartyShare(1, $ownership['id'], 10, '100', 9), 'Closed Ownership must reject share updates.');
ownershipValidation(fn () => $fixture['service']->removeParty(1, $ownership['id'], 10, 9), 'Closed Ownership must reject Party removal.');
ownershipValidation(fn () => $fixture['service']->designateActingOwner($ownership['id'], 10, [
    'ownership_party_id' => 1, 'basis_source' => 'authority',
], 9), 'Closed Ownership must reject designation mutation.');
ownershipValidation(fn () => $fixture['service']->changeActingOwner($ownership['id'], 10, [
    'ownership_party_id' => 1, 'basis_source' => 'authority',
], 9), 'Closed Ownership must reject designation changes.');
ownershipValidation(fn () => $fixture['service']->clearActingOwner($ownership['id'], 10, 9), 'Closed Ownership must reject designation clearing.');

foreach (['update:authorized_acting_owner_designations', 'update:ownerships'] as $failure) {
    $fixture = ownershipFixture();
    $ownership = recordOwnership($fixture['service'], ['100'], [
        'owner_id' => 1, 'basis_source' => 'authority',
    ]);
    $fixture['query']->failOperation = $failure;
    try { $fixture['service']->close($ownership['id'], 10, 9); } catch (RuntimeException) {}
    ownershipAssert($fixture['query']->tables['ownerships'][1]['status'] === 'current', 'Failed close must roll back Ownership state.');
    ownershipAssert($fixture['query']->tables['authorized_acting_owner_designations'][1]['ended_at'] === null, 'Failed close must preserve current designation.');
}

$fixture = ownershipFixture();
ownershipValidation(fn () => $fixture['service']->find(0, 10), 'Invalid public identifiers must be rejected.');
ownershipAssert($fixture['query']->operations === [], 'Invalid identifiers must not reach repositories.');

$fixture = ownershipFixture();
$ownership = recordOwnership($fixture['service']);
ownershipValidation(
    fn () => $fixture['service']->removeParty(1, $ownership['id'], 10, 9),
    'Removing the last Ownership Party must be rejected.'
);
ownershipAssert(count($fixture['query']->tables['ownership_parties']) === 1, 'Rejected last Party removal must preserve the Party.');
ownershipAssert($fixture['query']->tables['ownerships'][1]['status'] === 'current', 'Rejected last Party removal must preserve current Ownership.');

$fixture = ownershipFixture();
$ownership = recordOwnership($fixture['service'], ['60', null]);
$ulidCallsBeforeAdd = $fixture['ulids']->calls;
$addedParty = $fixture['service']->addParty($ownership['id'], 10, [
    'owner_id' => 3, 'share_percentage' => '20.5',
], 9);
ownershipAssert(isset($fixture['query']->tables['ownership_parties'][$addedParty['id']]), 'Added Party must be persisted.');
ownershipAssert((int) $addedParty['owner_id'] === 3, 'Added Party must retain the requested Owner.');
ownershipAssert($addedParty['share_percentage'] === '20.5000', 'Added Party share must be normalized.');
ownershipAssert((int) $addedParty['ownership_id'] === (int) $ownership['id'], 'Added Party must belong to the same Ownership.');
ownershipAssert((int) $addedParty['organization_id'] === 10, 'Added Party must belong to the same Organization.');
ownershipAssert($fixture['ulids']->calls === $ulidCallsBeforeAdd + 1, 'Successful Party addition must generate exactly one ULID.');

$fixture = ownershipFixture();
$ownership = recordOwnership($fixture['service'], ['50', '50'], [
    'owner_id' => 1, 'basis_source' => 'written authority',
]);
$fixture['query']->failOperation = 'insert:authorized_acting_owner_designations';
$changeFailed = false;
try {
    $fixture['service']->changeActingOwner($ownership['id'], 10, [
        'ownership_party_id' => 2, 'basis_source' => 'replacement authority',
    ], 9);
} catch (RuntimeException) {
    $changeFailed = true;
}
ownershipAssert($changeFailed, 'Replacement designation insertion failure must propagate.');
ownershipAssert(in_array('transaction:rollback', $fixture['database']->events, true), 'Failed Acting Owner change must roll back.');
ownershipAssert(count($fixture['query']->tables['authorized_acting_owner_designations']) === 1, 'Failed Acting Owner change must not retain a replacement designation.');
$originalDesignation = $fixture['query']->tables['authorized_acting_owner_designations'][1];
ownershipAssert($originalDesignation['ended_at'] === null, 'Failed Acting Owner change must restore the original ended_at value.');
ownershipAssert((int) $originalDesignation['current_ownership_guard'] === (int) $ownership['id'], 'Failed Acting Owner change must restore the original current designation.');

$fixture = ownershipFixture();
ownershipValidation(fn () => $fixture['service']->designateActingOwner(0, 10, [
    'ownership_party_id' => 1, 'basis_source' => 'authority',
], 9), 'Designate must reject invalid Ownership identifiers.');
ownershipValidation(fn () => $fixture['service']->changeActingOwner(1, 0, [
    'ownership_party_id' => 1, 'basis_source' => 'authority',
], 9), 'Change must reject invalid Organization identifiers.');
ownershipValidation(
    fn () => $fixture['service']->clearActingOwner('', 10, 9),
    'Clear must reject invalid Ownership identifiers.'
);
ownershipAssert($fixture['query']->operations === [], 'Invalid Acting Owner identifiers must not reach repositories.');
ownershipAssert($fixture['database']->events === [], 'Invalid Acting Owner identifiers must fail before transactions.');

$fixture = ownershipFixture();
$ownership = recordOwnership($fixture['service']);
ownershipValidation(fn () => $fixture['service']->designateActingOwner($ownership['id'], 10, [
    'ownership_party_id' => 1, 'basis_source' => '   ',
], 9), 'Blank basis source must be rejected.');
ownershipValidation(fn () => $fixture['service']->designateActingOwner($ownership['id'], 10, [
    'ownership_party_id' => 1, 'basis_source' => str_repeat('a', 256),
], 9), 'Basis source longer than 255 characters must be rejected.');
$basisDesignation = $fixture['service']->designateActingOwner($ownership['id'], 10, [
    'ownership_party_id' => 1, 'basis_source' => '  ordinary recorded authority  ',
], 9);
ownershipAssert($basisDesignation['basis_source'] === 'ordinary recorded authority', 'Valid basis source must be trimmed and accepted.');

echo "Ownership Service tests passed.\n";
