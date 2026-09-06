<?php

declare(strict_types=1);

use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\Database\QueryBuilderInterface;
use App\Exceptions\ValidationException;
use App\Modules\Owner\Repositories\OwnerRepository;
use App\Modules\Owner\Services\OwnerService;
use App\Modules\Property\Repositories\OrganizationPropertyRepository;
use App\Modules\Property\Repositories\PropertyOwnerLifecycleHistoryRepository;
use App\Modules\Property\Services\OrganizationPropertyService;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

final class ServiceUlidGenerator implements UlidGeneratorInterface
{
    public int $calls = 0;

    public function generate(): string
    {
        return str_pad('TEST' . ++$this->calls, 26, '0');
    }
}

final class ServiceDatabaseSpy implements DatabaseConnectionInterface
{
    /** @var array<int, string> */
    public array $events = [];

    public function connection(): PDO
    {
        throw new RuntimeException('Direct database access is not expected.');
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
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Throwable $exception) {
            $this->rollback();
            throw $exception;
        }
    }
}

final class ServiceQueryBuilderSpy implements QueryBuilderInterface
{
    /** @var array<int, array<string, mixed>|null> */
    public array $firstRows = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    public array $getRows = [];

    /** @var array<int, array{type: string, table: string, data?: array<string, mixed>, conditions?: array<int, array{0: string, 1: string, 2: mixed}>, lock?: bool}> */
    public array $operations = [];

    public bool $failHistoryInsert = false;

    private string $tableName = '';
    /** @var array<int, array{0: string, 1: string, 2: mixed}> */
    private array $conditions = [];
    private bool $locked = false;

    public function __construct(private ServiceDatabaseSpy $database)
    {
    }

    public function table(string $table): self
    {
        $this->tableName = $table;
        return $this;
    }

    public function select(array $columns = ['*']): self
    {
        return $this;
    }

    public function where(string $field, string $operator, mixed $value): self
    {
        $this->conditions[] = [$field, $operator, $value];
        return $this;
    }

    public function orWhere(string $field, string $operator, mixed $value): self
    {
        return $this->where($field, $operator, $value);
    }

    public function whereIn(string $field, array $values): self
    {
        $this->conditions[] = [$field, 'IN', $values];
        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        return $this;
    }

    public function limit(int $limit): self
    {
        return $this;
    }

    public function offset(int $offset): self
    {
        return $this;
    }

    public function forUpdate(): self
    {
        $this->locked = true;
        return $this;
    }

    public function first(): ?array
    {
        $this->record('first');
        return array_shift($this->firstRows);
    }

    public function get(): array
    {
        $this->record('get');
        return array_shift($this->getRows) ?? [];
    }

    public function insert(array $data): int
    {
        $table = $this->tableName;
        $this->record('insert', $data);
        if ($table === 'property_owner_lifecycle_history' && $this->failHistoryInsert) {
            throw new RuntimeException('History append failed.');
        }
        return 1;
    }

    public function update(array $data): int
    {
        $this->record('update', $data);
        return 1;
    }

    public function delete(): int
    {
        $this->record('delete');
        return 1;
    }

    /** @param array<string, mixed>|null $data */
    private function record(string $type, ?array $data = null): void
    {
        $operation = [
            'type' => $type,
            'table' => $this->tableName,
            'conditions' => $this->conditions,
            'lock' => $this->locked,
        ];
        if ($data !== null) {
            $operation['data'] = $data;
        }
        $this->operations[] = $operation;
        $this->database->events[] = $type . ':' . $this->tableName . ($this->locked ? ':lock' : '');
        $this->tableName = '';
        $this->conditions = [];
        $this->locked = false;
    }
}

/** @return array{property: OrganizationPropertyService, owner: OwnerService, query: ServiceQueryBuilderSpy, database: ServiceDatabaseSpy, ulid: ServiceUlidGenerator} */
function serviceFixture(): array
{
    $database = new ServiceDatabaseSpy();
    $query = new ServiceQueryBuilderSpy($database);
    $ulid = new ServiceUlidGenerator();
    $history = new PropertyOwnerLifecycleHistoryRepository($query);

    return [
        'property' => new OrganizationPropertyService(
            new OrganizationPropertyRepository($query), $history, $database, $ulid
        ),
        'owner' => new OwnerService(new OwnerRepository($query), $history, $database, $ulid),
        'query' => $query,
        'database' => $database,
        'ulid' => $ulid,
    ];
}

function serviceAssert(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function serviceExpectValidation(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (ValidationException) {
        return;
    }
    throw new RuntimeException($message);
}

/** @return array<string, mixed> */
function operation(ServiceQueryBuilderSpy $query, string $type, string $table, int $occurrence = 0): array
{
    $matches = array_values(array_filter(
        $query->operations,
        static fn (array $operation): bool => $operation['type'] === $type && $operation['table'] === $table
    ));
    return $matches[$occurrence] ?? throw new RuntimeException("Missing {$type} operation for {$table}.");
}

$fixture = serviceFixture();
serviceExpectValidation(fn () => $fixture['property']->find(0, 10), 'Property find must reject an invalid Property ID.');
serviceExpectValidation(fn () => $fixture['property']->find(1, '0'), 'Property find must reject an invalid Organization ID.');
serviceExpectValidation(
    fn () => $fixture['property']->update('', 10, ['property_label' => 'Unit'], 7),
    'Property update must reject invalid identifiers.'
);
serviceExpectValidation(
    fn () => $fixture['property']->archive(1, -1, 7),
    'Property lifecycle must reject invalid identifiers.'
);
serviceAssert($fixture['query']->operations === [], 'Invalid Property identifiers must not reach repositories.');
serviceAssert($fixture['database']->events === [], 'Invalid Property identifiers must fail before transactions.');
serviceAssert($fixture['property']->all([]) === [], 'Empty Property scope must remain empty.');
serviceExpectValidation(fn () => $fixture['property']->all([10, 0]), 'Property list must reject invalid Organization IDs.');
serviceAssert($fixture['query']->operations === [], 'Invalid Property list scope must not reach repositories.');

$fixture = serviceFixture();
serviceExpectValidation(fn () => $fixture['owner']->find(0, 20), 'Owner find must reject an invalid Owner ID.');
serviceExpectValidation(fn () => $fixture['owner']->find(2, ''), 'Owner find must reject an invalid Organization ID.');
serviceExpectValidation(
    fn () => $fixture['owner']->update(-1, 20, ['display_name' => 'Owner'], 8),
    'Owner update must reject invalid identifiers.'
);
serviceExpectValidation(
    fn () => $fixture['owner']->deactivate(2, 20, 0),
    'Owner lifecycle must reject invalid identifiers.'
);
serviceAssert($fixture['query']->operations === [], 'Invalid Owner identifiers must not reach repositories.');
serviceAssert($fixture['database']->events === [], 'Invalid Owner identifiers must fail before transactions.');
serviceAssert($fixture['owner']->all([]) === [], 'Empty Owner scope must remain empty.');
serviceExpectValidation(fn () => $fixture['owner']->all(['bad']), 'Owner list must reject invalid Organization IDs.');
serviceExpectValidation(
    fn () => $fixture['owner']->searchByDisplayName(20, '   '),
    'Display-name search must reject whitespace-only input.'
);
serviceExpectValidation(
    fn () => $fixture['owner']->searchByMobile(20, '   '),
    'Mobile search must reject whitespace-only input.'
);
serviceExpectValidation(
    fn () => $fixture['owner']->searchByEmail(20, '   '),
    'Email search must reject whitespace-only input.'
);
serviceExpectValidation(
    fn () => $fixture['owner']->searchByEmail(0, 'owner@example.com'),
    'Owner search must validate Organization ID.'
);
serviceAssert($fixture['query']->operations === [], 'Invalid Owner searches must not reach repositories.');

$fixture = serviceFixture();
$fixture['query']->firstRows[] = ['id' => 1, 'organization_id' => 10, 'status' => 'active'];
$createdProperty = $fixture['property']->create(['organization_id' => 10, 'property_label' => '  Unit 10  '], 7);
$propertyInsert = operation($fixture['query'], 'insert', 'organization_properties');
serviceAssert($fixture['ulid']->calls === 1, 'Property create must generate one ULID through the contract.');
serviceAssert($propertyInsert['data']['status'] === 'active', 'Property create must default to active.');
serviceAssert($propertyInsert['data']['property_label'] === 'Unit 10', 'Property label must be trimmed.');
serviceAssert($createdProperty['organization_id'] === 10, 'Property create result failed.');

$fixture = serviceFixture();
$fixture['query']->firstRows[] = ['id' => 1, 'organization_id' => 10, 'status' => 'active'];
$fixture['property']->find(1, 10);
$find = operation($fixture['query'], 'first', 'organization_properties');
serviceAssert($find['conditions'][1][2] === 10, 'Property get must preserve Organization scope.');
serviceAssert($fixture['property']->all([]) === [], 'Empty Property list scope must return no records.');

$fixture = serviceFixture();
$fixture['query']->firstRows = [
    ['id' => 1, 'organization_id' => 10, 'property_label' => 'Old', 'status' => 'active'],
    ['id' => 1, 'organization_id' => 10, 'property_label' => 'New', 'status' => 'active'],
];
$fixture['property']->update(1, 10, ['property_label' => 'New', 'status' => 'archived'], 7);
$propertyUpdate = operation($fixture['query'], 'update', 'organization_properties');
serviceAssert(! array_key_exists('status', $propertyUpdate['data']), 'Generic Property update must reject lifecycle fields.');

$fixture = serviceFixture();
$fixture['query']->firstRows = [
    ['id' => 1, 'organization_id' => 10, 'status' => 'active'],
    ['id' => 1, 'organization_id' => 10, 'status' => 'archived'],
    ['id' => 1, 'organization_id' => 10],
];
$fixture['property']->archive(1, 10, 7);
$archive = operation($fixture['query'], 'update', 'organization_properties');
$propertyHistory = operation($fixture['query'], 'insert', 'property_owner_lifecycle_history');
serviceAssert($archive['data']['status'] === 'archived', 'Active Property archive failed.');
serviceAssert($archive['data']['archived_at'] === $propertyHistory['data']['created_at'], 'Property history timestamp must match mutation.');
serviceAssert($fixture['database']->events === [
    'transaction:begin', 'first:organization_properties:lock', 'update:organization_properties',
    'first:organization_properties', 'insert:property_owner_lifecycle_history',
    'first:property_owner_lifecycle_history', 'transaction:commit',
], 'Property lock, mutation, history, and transaction ordering is invalid.');

$fixture = serviceFixture();
$fixture['query']->firstRows[] = ['id' => 1, 'organization_id' => 10, 'status' => 'archived'];
serviceExpectValidation(fn () => $fixture['property']->archive(1, 10, 7), 'Repeated Property archive must fail.');
serviceAssert(in_array('transaction:rollback', $fixture['database']->events, true), 'Invalid Property transition must roll back.');

$fixture = serviceFixture();
$fixture['query']->firstRows = [
    ['id' => 1, 'organization_id' => 10, 'status' => 'archived'],
    ['id' => 1, 'organization_id' => 10, 'status' => 'active'],
    ['id' => 1, 'organization_id' => 10],
];
serviceAssert($fixture['property']->reactivate(1, 10, 7)['status'] === 'active', 'Archived Property reactivation failed.');

$fixture = serviceFixture();
$fixture['query']->firstRows[] = ['id' => 1, 'organization_id' => 10, 'status' => 'active'];
serviceExpectValidation(fn () => $fixture['property']->reactivate(1, 10, 7), 'Repeated Property reactivation must fail.');

$fixture = serviceFixture();
$fixture['query']->firstRows = [
    ['id' => 1, 'organization_id' => 10, 'status' => 'active'],
    ['id' => 1, 'organization_id' => 10, 'status' => 'archived'],
];
$fixture['query']->failHistoryInsert = true;
try {
    $fixture['property']->archive(1, 10, 7);
    throw new RuntimeException('History failure must escape the Property service.');
} catch (RuntimeException $exception) {
    serviceAssert($exception->getMessage() === 'History append failed.', 'Property history failure was hidden.');
}
serviceAssert(end($fixture['database']->events) === 'transaction:rollback', 'Property history failure must roll back.');

$fixture = serviceFixture();
$fixture['query']->firstRows[] = ['id' => 2, 'organization_id' => 20, 'status' => 'active'];
$fixture['owner']->create([
    'organization_id' => 20, 'party_type' => 'individual', 'display_name' => '  Owner  ',
    'email' => ' Owner@Example.COM ', 'mobile' => '  +20 100 200  ',
], 8);
$ownerInsert = operation($fixture['query'], 'insert', 'owners');
serviceAssert($fixture['ulid']->calls === 1, 'Owner create must generate one ULID.');
serviceAssert($ownerInsert['data']['party_type'] === 'individual', 'Individual Owner creation failed.');
serviceAssert($ownerInsert['data']['email'] === 'Owner@Example.COM', 'Original Owner email must be trimmed.');
serviceAssert($ownerInsert['data']['email_normalized'] === 'owner@example.com', 'Owner email normalization failed.');
serviceAssert($ownerInsert['data']['mobile'] === '+20 100 200', 'Original Owner mobile must be trimmed.');
serviceAssert($ownerInsert['data']['mobile_normalized'] === '+20 100 200', 'Owner mobile normalization must be trim-only.');

$fixture = serviceFixture();
$fixture['query']->firstRows[] = ['id' => 2, 'organization_id' => 20, 'status' => 'active'];
$fixture['owner']->create(['organization_id' => 20, 'party_type' => 'legal_entity', 'display_name' => 'Company'], 8);
serviceAssert(operation($fixture['query'], 'insert', 'owners')['data']['party_type'] === 'legal_entity', 'Legal-entity creation failed.');

$fixture = serviceFixture();
serviceExpectValidation(
    fn () => $fixture['owner']->create(['organization_id' => 20, 'party_type' => 'unknown', 'display_name' => 'Owner'], 8),
    'Invalid Owner party type must fail.'
);
serviceExpectValidation(
    fn () => $fixture['owner']->create([
        'organization_id' => 20, 'party_type' => 'individual', 'display_name' => 'Owner',
        'preferred_contact_method' => 'sms',
    ], 8),
    'Invalid preferred contact method must fail.'
);

$fixture = serviceFixture();
$fixture['query']->firstRows[] = ['id' => 2, 'organization_id' => 20, 'status' => 'active'];
$fixture['owner']->find(2, 20);
serviceAssert(operation($fixture['query'], 'first', 'owners')['conditions'][1][2] === 20, 'Owner get must preserve Organization scope.');
$fixture['query']->getRows[] = [];
$fixture['owner']->searchByEmail(20, ' OWNER@example.COM ');
$ownerSearch = operation($fixture['query'], 'get', 'owners');
serviceAssert($ownerSearch['conditions'][0][2] === 20, 'Owner search must remain in one Organization.');
serviceAssert($ownerSearch['conditions'][1][2] === 'owner@example.com', 'Owner email search must normalize input.');

$fixture = serviceFixture();
$fixture['query']->firstRows = [
    ['id' => 2, 'organization_id' => 20, 'party_type' => 'individual', 'display_name' => 'Owner', 'status' => 'active'],
    ['id' => 2, 'organization_id' => 20, 'status' => 'active'],
];
$fixture['owner']->update(2, 20, ['email' => ' NEW@Example.COM ', 'mobile' => ' 0100 '], 8);
$ownerUpdate = operation($fixture['query'], 'update', 'owners');
serviceAssert($ownerUpdate['data']['email_normalized'] === 'new@example.com', 'Owner update must recompute normalized email.');
serviceAssert($ownerUpdate['data']['mobile_normalized'] === '0100', 'Owner update must recompute normalized mobile.');

$fixture = serviceFixture();
$fixture['query']->firstRows = [
    ['id' => 2, 'organization_id' => 20, 'status' => 'active'],
    ['id' => 2, 'organization_id' => 20, 'status' => 'inactive'],
    ['id' => 1, 'organization_id' => 20],
];
$fixture['owner']->deactivate(2, 20, 8);
$deactivate = operation($fixture['query'], 'update', 'owners');
$ownerHistory = operation($fixture['query'], 'insert', 'property_owner_lifecycle_history');
serviceAssert($deactivate['data']['status'] === 'inactive', 'Active Owner deactivation failed.');
serviceAssert($deactivate['data']['deactivated_at'] === $ownerHistory['data']['created_at'], 'Owner history timestamp must match mutation.');
serviceAssert($fixture['database']->events[1] === 'first:owners:lock', 'Owner row must lock before mutation.');
serviceAssert(end($fixture['database']->events) === 'transaction:commit', 'Owner lifecycle must commit atomically.');

$fixture = serviceFixture();
$fixture['query']->firstRows[] = ['id' => 2, 'organization_id' => 20, 'status' => 'inactive'];
serviceExpectValidation(fn () => $fixture['owner']->deactivate(2, 20, 8), 'Repeated Owner deactivation must fail.');

$fixture = serviceFixture();
$fixture['query']->firstRows = [
    ['id' => 2, 'organization_id' => 20, 'status' => 'inactive'],
    ['id' => 2, 'organization_id' => 20, 'status' => 'active'],
    ['id' => 1, 'organization_id' => 20],
];
serviceAssert($fixture['owner']->reactivate(2, 20, 8)['status'] === 'active', 'Inactive Owner reactivation failed.');

$fixture = serviceFixture();
$fixture['query']->firstRows[] = ['id' => 2, 'organization_id' => 20, 'status' => 'active'];
serviceExpectValidation(fn () => $fixture['owner']->reactivate(2, 20, 8), 'Repeated Owner reactivation must fail.');

foreach ([OrganizationPropertyService::class, OwnerService::class] as $serviceClass) {
    $source = file_get_contents((new ReflectionClass($serviceClass))->getFileName());
    serviceAssert(! str_contains($source, 'SELECT '), "{$serviceClass} must not construct SQL.");
    serviceAssert(! str_contains($source, 'Symfony\\Component\\Uid'), "{$serviceClass} must not depend directly on Symfony ULID.");
}

echo "BF013 Property and Owner Service tests passed.\n";
