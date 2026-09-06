<?php

declare(strict_types=1);

use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\Database\QueryBuilderInterface;
use App\Exceptions\ValidationException;
use App\Modules\GlobalPropertyIdentity\Repositories\GlobalPhysicalIdentityLinkRepository;
use App\Modules\GlobalPropertyIdentity\Repositories\GlobalPhysicalPropertyIdentityRepository;
use App\Modules\GlobalPropertyIdentity\Services\GlobalPhysicalIdentityService;
use App\Modules\Property\Repositories\OrganizationPropertyRepository;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

final class GlobalIdentityTestUlids implements UlidGeneratorInterface
{
    public int $calls = 0;

    public function generate(): string
    {
        return str_pad('GLOBAL' . ++$this->calls, 26, '0');
    }
}

final class GlobalIdentityTestDatabase implements DatabaseConnectionInterface
{
    public array $events = [];
    public ?GlobalIdentityTestQueryBuilder $query = null;

    public function connection(): PDO
    {
        throw new RuntimeException('Direct SQL is not expected.');
    }

    public function beginTransaction(): void { $this->events[] = 'transaction:begin'; }
    public function commit(): void { $this->events[] = 'transaction:commit'; }
    public function rollback(): void { $this->events[] = 'transaction:rollback'; }

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

final class GlobalIdentityTestQueryBuilder implements QueryBuilderInterface
{
    /** @var array<string, array<int, array<string, mixed>>> */
    public array $tables;
    public array $operations = [];
    public ?string $failOperation = null;
    public ?string $ignoreUpdateTable = null;
    private string $tableName = '';
    private array $conditions = [];
    private array $orders = [];
    private bool $locked = false;

    public function __construct(private GlobalIdentityTestDatabase $database)
    {
        $this->tables = [
            'organization_properties' => [
                1 => ['id' => 1, 'organization_id' => 10, 'property_label' => 'Unit 1', 'status' => 'active'],
                2 => ['id' => 2, 'organization_id' => 20, 'property_label' => 'Unit 2', 'status' => 'active'],
            ],
            'global_physical_property_identities' => [
                1 => ['id' => 1, 'ulid' => 'IDENTITY1', 'created_by_user_id' => 7, 'updated_by_user_id' => 7],
                2 => ['id' => 2, 'ulid' => 'IDENTITY2', 'created_by_user_id' => 7, 'updated_by_user_id' => 7],
            ],
            'global_physical_identity_links' => [],
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
        if ($table === 'global_physical_identity_links') {
            $data['active_property_guard'] = $data['unlinked_at'] === null
                ? $data['organization_property_id']
                : null;
        }
        $this->tables[$table][$id] = $data;
        $this->record('insert', $data);
        return $id;
    }

    public function update(array $data): int
    {
        $table = $this->tableName;
        $this->maybeFail('update:' . $table);
        if ($this->ignoreUpdateTable === $table) {
            $this->record('update', $data);
            return 0;
        }
        $count = 0;
        foreach ($this->tables[$table] as $id => $row) {
            if ($this->matches($row)) {
                $this->tables[$table][$id] = array_merge($row, $data);
                if ($table === 'global_physical_identity_links' && ($data['unlinked_at'] ?? null) !== null) {
                    $this->tables[$table][$id]['active_property_guard'] = null;
                }
                $count++;
            }
        }
        $this->record('update', $data);
        return $count;
    }

    public function delete(): int
    {
        throw new RuntimeException('Delete is not expected.');
    }

    private function matchingRows(): array
    {
        $rows = array_values(array_filter(
            $this->tables[$this->tableName] ?? [],
            fn (array $row): bool => $this->matches($row)
        ));
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
        $operation = [
            'type' => $type, 'table' => $this->tableName,
            'conditions' => $this->conditions, 'lock' => $this->locked,
        ];
        if ($data !== null) { $operation['data'] = $data; }
        $this->operations[] = $operation;
        $this->database->events[] = $type . ':' . $this->tableName . ($this->locked ? ':lock' : '');
        $this->tableName = '';
        $this->conditions = [];
        $this->orders = [];
        $this->locked = false;
    }
}

function globalIdentityFixture(): array
{
    $database = new GlobalIdentityTestDatabase();
    $query = new GlobalIdentityTestQueryBuilder($database);
    $ulids = new GlobalIdentityTestUlids();
    $service = new GlobalPhysicalIdentityService(
        new GlobalPhysicalPropertyIdentityRepository($query),
        new GlobalPhysicalIdentityLinkRepository($query),
        new OrganizationPropertyRepository($query),
        $database,
        $ulids
    );
    return compact('database', 'query', 'ulids', 'service');
}

function globalIdentityAssert(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
}

function globalIdentityValidation(callable $callback, string $message): void
{
    try { $callback(); } catch (ValidationException) { return; }
    throw new RuntimeException($message);
}

$fixture = globalIdentityFixture();
$createdIdentity = $fixture['service']->create(9);
globalIdentityAssert($createdIdentity['created_by_user_id'] === 9 && $createdIdentity['updated_by_user_id'] === 9, 'Identity audit fields were not persisted.');
globalIdentityAssert($fixture['ulids']->calls === 1, 'Identity creation must generate one ULID.');
globalIdentityAssert($fixture['service']->find($createdIdentity['id'])['id'] === $createdIdentity['id'], 'Created Identity must be findable.');
globalIdentityAssert($fixture['service']->find(99) === null, 'Missing Identity find must return null.');
globalIdentityAssert(count($fixture['service']->all()) === 3, 'Identity list must return all identities.');

$fixture = globalIdentityFixture();
$link = $fixture['service']->link(1, 10, 1, 9);
globalIdentityAssert($link['link_method'] === 'manual', 'Link method must be manual.');
globalIdentityAssert($link['unlinked_at'] === null && $link['unlinked_by_user_id'] === null && $link['unlink_reason'] === null, 'New link unlink fields must be null.');
globalIdentityAssert($fixture['ulids']->calls === 1, 'Link creation must generate one ULID.');
$events = $fixture['database']->events;
$propertyLock = array_search('first:organization_properties:lock', $events, true);
$identityRead = array_search('first:global_physical_property_identities', $events, true);
$activeLock = array_search('first:global_physical_identity_links:lock', $events, true);
globalIdentityAssert($propertyLock < $identityRead && $identityRead < $activeLock, 'Link lock/read order is incorrect.');
globalIdentityValidation(fn () => $fixture['service']->link(1, 10, 2, 9), 'Duplicate active link must be rejected.');

foreach ([[99, 10, 1], [1, 20, 1], [1, 10, 99]] as [$propertyId, $organizationId, $identityId]) {
    $fixture = globalIdentityFixture();
    globalIdentityValidation(
        fn () => $fixture['service']->link($propertyId, $organizationId, $identityId, 9),
        'Missing or cross-Organization link resource must be rejected.'
    );
}

$fixture = globalIdentityFixture();
$fixture['query']->failOperation = 'insert:global_physical_identity_links';
try { $fixture['service']->link(1, 10, 1, 9); } catch (RuntimeException) {}
globalIdentityAssert($fixture['query']->tables['global_physical_identity_links'] === [], 'Failed link insertion must roll back.');
globalIdentityAssert(in_array('transaction:rollback', $fixture['database']->events, true), 'Failed link must roll back its transaction.');

$fixture = globalIdentityFixture();
$link = $fixture['service']->link(1, 10, 1, 9);
$ended = $fixture['service']->unlink(1, 10, '  duplicate registration corrected  ', 8);
globalIdentityAssert($ended['id'] === $link['id'], 'Unlink must end the exact active link.');
globalIdentityAssert($ended['unlink_reason'] === 'duplicate registration corrected', 'Unlink reason must be trimmed.');
globalIdentityAssert($ended['unlinked_by_user_id'] === 8 && $ended['unlinked_at'] !== null, 'Unlink audit fields were not persisted.');
globalIdentityAssert(count($fixture['service']->linkHistoryForProperty(1, 10)) === 1, 'Unlink must preserve link history.');
globalIdentityValidation(fn () => $fixture['service']->unlink(1, 10, 'reason', 8), 'Unlink without active link must be rejected.');

$fixture = globalIdentityFixture();
globalIdentityValidation(fn () => $fixture['service']->unlink(1, 10, '   ', 8), 'Blank unlink reason must be rejected.');
globalIdentityAssert($fixture['query']->operations === [] && $fixture['database']->events === [], 'Blank reason must fail before persistence.');

$fixture = globalIdentityFixture();
$fixture['service']->link(1, 10, 1, 9);
$fixture['query']->ignoreUpdateTable = 'global_physical_identity_links';
try { $fixture['service']->unlink(1, 10, 'stale update', 8); } catch (RuntimeException) {}
globalIdentityAssert($fixture['query']->tables['global_physical_identity_links'][1]['unlinked_at'] === null, 'Stale unlink result must roll back.');
globalIdentityAssert(in_array('transaction:rollback', $fixture['database']->events, true), 'Stale unlink result must cause rollback.');

$fixture = globalIdentityFixture();
$fixture['service']->link(1, 10, 1, 9);
$operationsBeforeRelink = count($fixture['query']->operations);
$ulidsBeforeRelink = $fixture['ulids']->calls;
$replacement = $fixture['service']->relink(1, 10, 2, '  corrected identity  ', 8);
globalIdentityAssert($replacement['global_physical_property_identity_id'] === 2, 'Relink must target the replacement Identity.');
globalIdentityAssert($replacement['link_method'] === 'manual' && $replacement['unlinked_at'] === null, 'Replacement must be a new manual active link.');
globalIdentityAssert($fixture['ulids']->calls === $ulidsBeforeRelink + 1, 'Replacement link must receive one ULID.');
$history = $fixture['service']->linkHistoryForProperty(1, 10);
globalIdentityAssert(count($history) === 2, 'Relink must preserve old and new link rows.');
globalIdentityAssert($fixture['query']->tables['global_physical_identity_links'][1]['unlink_reason'] === 'corrected identity', 'Relink must end the old link with the trimmed reason.');
$relinkOperations = array_slice($fixture['query']->operations, $operationsBeforeRelink);
$relinkTables = array_map(static fn (array $operation): string => $operation['table'], $relinkOperations);
globalIdentityAssert(array_search('global_physical_identity_links', $relinkTables, true) < array_search('global_physical_property_identities', $relinkTables, true), 'Relink must inspect the active link before the target Identity.');

$fixture = globalIdentityFixture();
$fixture['service']->link(1, 10, 1, 9);
globalIdentityValidation(fn () => $fixture['service']->relink(1, 10, 1, 'same', 8), 'Same-Identity relink must be rejected.');
globalIdentityValidation(fn () => $fixture['service']->relink(2, 20, 1, 'missing active', 8), 'Relink without active link must be rejected.');
globalIdentityValidation(fn () => $fixture['service']->relink(1, 10, 99, 'missing identity', 8), 'Relink to missing Identity must be rejected.');
globalIdentityValidation(fn () => $fixture['service']->relink(1, 10, 2, '  ', 8), 'Relink reason must be required.');

$fixture = globalIdentityFixture();
$fixture['service']->link(1, 10, 1, 9);
$fixture['query']->failOperation = 'insert:global_physical_identity_links';
try { $fixture['service']->relink(1, 10, 2, 'replacement failed', 8); } catch (RuntimeException) {}
globalIdentityAssert(count($fixture['query']->tables['global_physical_identity_links']) === 1, 'Failed relink must not retain replacement row.');
globalIdentityAssert($fixture['query']->tables['global_physical_identity_links'][1]['unlinked_at'] === null, 'Failed relink must restore original active link.');

$fixture = globalIdentityFixture();
$fixture['service']->link(1, 10, 1, 9);
globalIdentityAssert($fixture['service']->activeLinkForProperty(1, 10)['id'] === 1, 'Scoped active-link read failed.');
globalIdentityValidation(fn () => $fixture['service']->activeLinkForProperty(1, 20), 'Cross-Organization active-link read must be rejected.');
globalIdentityAssert(count($fixture['service']->representationsForIdentity(1)) === 1, 'Identity representations read failed.');
globalIdentityValidation(fn () => $fixture['service']->representationsForIdentity(99), 'Missing Identity representations must be rejected.');
$property = $fixture['query']->tables['organization_properties'][1];
globalIdentityAssert(! array_key_exists('global_physical_property_identity_id', $property), 'Ordinary Property data must not expose Global Identity.');

$fixture = globalIdentityFixture();
globalIdentityValidation(fn () => $fixture['service']->create(0), 'Invalid actor ID must be rejected.');
globalIdentityValidation(fn () => $fixture['service']->find(''), 'Invalid Identity ID must be rejected.');
globalIdentityValidation(fn () => $fixture['service']->link(0, 10, 1, 9), 'Invalid Property ID must be rejected.');
globalIdentityValidation(fn () => $fixture['service']->unlink(1, 0, 'reason', 9), 'Invalid Organization ID must be rejected.');
globalIdentityValidation(fn () => $fixture['service']->relink(1, 10, -1, 'reason', 9), 'Invalid replacement Identity ID must be rejected.');
globalIdentityAssert($fixture['query']->operations === [] && $fixture['database']->events === [], 'Invalid identifiers must fail before persistence or transactions.');

echo "Global Physical Identity Service tests passed.\n";
