<?php

declare(strict_types=1);

use App\Core\Database\QueryBuilderInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Modules\Authorization\Services\AuthorizationService;
use App\Modules\Authorization\Services\OrganizationScopeService;
use App\Modules\Authorization\Middleware\AuthorizationMiddleware;
use App\Modules\Franchise\Repositories\FranchiseRepository;
use App\Modules\Organization\Repositories\OrganizationRepository;
use App\Modules\Owner\Repositories\OwnerRepository;
use App\Modules\Ownership\Repositories\OwnershipRepository;
use App\Modules\PartnerAgency\Repositories\PartnerAgencyRepository;
use App\Modules\Permission\Repositories\PermissionRepository;
use App\Modules\Permission\Repositories\PositionPermissionRepository;
use App\Modules\Position\Repositories\PositionRepository;
use App\Modules\Property\Repositories\OrganizationPropertyRepository;
use App\Modules\User\Repositories\UserRepository;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

final class BF013AuthorizationDatabase implements DatabaseConnectionInterface
{
    public function connection(): PDO { throw new RuntimeException('Direct database access is not expected.'); }
    public function beginTransaction(): void {}
    public function commit(): void {}
    public function rollback(): void {}
    public function transaction(callable $callback): mixed { return $callback($this); }
}

final class BF013AuthorizationQueryBuilder implements QueryBuilderInterface
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $tables;
    private string $tableName = '';
    private array $conditions = [];

    public function __construct()
    {
        $codes = [
            'properties.view', 'properties.manage', 'owners.view', 'owners.manage',
            'ownerships.view', 'ownerships.manage',
            'global_physical_identities.view', 'global_physical_identities.manage',
        ];
        $permissions = [];
        $assignments = [];
        foreach ($codes as $index => $code) {
            $id = $index + 1;
            $permissions[$id] = ['id' => $id, 'code' => $code, 'status' => 'active'];
            foreach ([10, 20, 30] as $positionId) {
                $assignments[] = ['id' => count($assignments) + 1, 'position_id' => $positionId, 'permission_id' => $id];
            }
        }
        $this->tables = [
            'organizations' => [
                1 => ['id' => 1, 'organization_type' => 'system', 'parent_organization_id' => null],
                2 => ['id' => 2, 'organization_type' => 'franchise', 'parent_organization_id' => 1],
                3 => ['id' => 3, 'organization_type' => 'partner_agency', 'parent_organization_id' => 2],
                4 => ['id' => 4, 'organization_type' => 'franchise', 'parent_organization_id' => 1],
                5 => ['id' => 5, 'organization_type' => 'partner_agency', 'parent_organization_id' => 4],
            ],
            'positions' => [
                10 => ['id' => 10, 'organization_id' => 1, 'status' => 'active'],
                11 => ['id' => 11, 'organization_id' => 1, 'status' => 'active'],
                20 => ['id' => 20, 'organization_id' => 2, 'status' => 'active'],
                30 => ['id' => 30, 'organization_id' => 3, 'status' => 'active'],
            ],
            'permissions' => $permissions,
            'position_permissions' => $assignments,
            'organization_properties' => [
                101 => ['id' => 101, 'organization_id' => 3],
            ],
            'owners' => [
                201 => ['id' => 201, 'organization_id' => 3],
            ],
            'ownerships' => [
                301 => ['id' => 301, 'organization_id' => 3],
            ],
            'franchises' => [],
            'partner_agencies' => [],
            'users' => [],
        ];
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
    public function orderBy(string $field, string $direction = 'ASC'): self { return $this; }
    public function limit(int $limit): self { return $this; }
    public function offset(int $offset): self { return $this; }
    public function forUpdate(): self { return $this; }
    public function first(): ?array
    {
        $rows = $this->matchingRows(); $this->reset(); return $rows[0] ?? null;
    }
    public function get(): array
    {
        $rows = $this->matchingRows(); $this->reset(); return $rows;
    }
    public function insert(array $data): int { throw new RuntimeException('Writes are not expected.'); }
    public function update(array $data): int { throw new RuntimeException('Writes are not expected.'); }
    public function delete(): int { throw new RuntimeException('Writes are not expected.'); }

    private function matchingRows(): array
    {
        return array_values(array_filter(
            $this->tables[$this->tableName] ?? [],
            function (array $row): bool {
                foreach ($this->conditions as [$field, $operator, $value]) {
                    $actual = $row[$field] ?? null;
                    if ($operator === '=' && $actual != $value) { return false; }
                    if ($operator === '!=' && $actual == $value) { return false; }
                    if ($operator === 'IN' && ! in_array($actual, $value, true)) { return false; }
                }
                return true;
            }
        ));
    }

    private function reset(): void
    {
        $this->tableName = '';
        $this->conditions = [];
    }
}

function bf013AuthorizationService(): AuthorizationService
{
    $query = new BF013AuthorizationQueryBuilder();
    $database = new BF013AuthorizationDatabase();
    $organizations = new OrganizationRepository($database, $query);
    return new AuthorizationService(
        new PositionRepository($database, $query),
        new PermissionRepository($database, $query),
        new PositionPermissionRepository($database, $query),
        new OrganizationScopeService($organizations),
        $organizations,
        new FranchiseRepository($database, $query),
        new PartnerAgencyRepository($database, $query),
        new UserRepository($database, $query),
        new OrganizationPropertyRepository($query),
        new OwnerRepository($query),
        new OwnershipRepository($query)
    );
}

function bf013AuthAssert(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
}

function bf013AuthAssertUnsupportedResource(AuthorizationService $service, string $resource): void
{
    try {
        $service->targetOrganization($resource, 1);
    } catch (InvalidArgumentException $exception) {
        bf013AuthAssert(
            str_contains($exception->getMessage(), $resource),
            'Unsupported-resource error must identify the resource.'
        );
        return;
    }

    throw new RuntimeException("Unsupported resource {$resource} must fail explicitly.");
}

$service = bf013AuthorizationService();
$system = ['organization_id' => 1, 'position_id' => 10, 'status' => 'active'];
$systemWithoutCapability = ['organization_id' => 1, 'position_id' => 11, 'status' => 'active'];
$franchise = ['organization_id' => 2, 'position_id' => 20, 'status' => 'active'];
$partnerAgency = ['organization_id' => 3, 'position_id' => 30, 'status' => 'active'];

$middlewareMode = (new ReflectionMethod(AuthorizationMiddleware::class, 'handle'))->getParameters()[4];
bf013AuthAssert(
    $middlewareMode->isDefaultValueAvailable() && $middlewareMode->getDefaultValue() === OrganizationScopeService::HIERARCHY,
    'AuthorizationMiddleware must default to hierarchy scope.'
);
bf013AuthAssert($service->scopeFor($system) === [1, 2, 3, 4, 5], 'Default System hierarchy scope changed.');
bf013AuthAssert($service->scopeFor($franchise) === [2, 3], 'Default Franchise hierarchy scope changed.');
bf013AuthAssert($service->scopeFor($partnerAgency) === [3], 'Default Partner Agency hierarchy scope changed.');
bf013AuthAssert($service->authorize($franchise, 'properties.view', 3) === null, 'Default authorization mode must remain hierarchy.');

bf013AuthAssert($service->authorize($system, 'owners.view', 3, OrganizationScopeService::PRIVATE_ORGANIZATION) === null, 'System private-resource scope must allow other Organizations.');
bf013AuthAssert($service->authorize($franchise, 'owners.view', 2, OrganizationScopeService::PRIVATE_ORGANIZATION) === null, 'Franchise private-resource scope must allow its own Organization.');
bf013AuthAssert($service->authorize($franchise, 'owners.view', 3, OrganizationScopeService::PRIVATE_ORGANIZATION) !== null, 'Franchise private-resource scope must deny child Partner Agencies.');
bf013AuthAssert($service->authorize($partnerAgency, 'ownerships.view', 3, OrganizationScopeService::PRIVATE_ORGANIZATION) === null, 'Partner Agency private-resource scope must allow its own Organization.');
bf013AuthAssert($service->authorize($partnerAgency, 'ownerships.view', 2, OrganizationScopeService::PRIVATE_ORGANIZATION) !== null, 'Partner Agency private-resource scope must deny other Organizations.');

bf013AuthAssert($service->authorize($system, 'global_physical_identities.view', null, OrganizationScopeService::SYSTEM_ONLY) === null, 'System actor with capability must pass system-only authorization.');
bf013AuthAssert($service->authorize($systemWithoutCapability, 'global_physical_identities.view', null, OrganizationScopeService::SYSTEM_ONLY) !== null, 'System actor without capability must be denied.');
bf013AuthAssert($service->authorize($franchise, 'global_physical_identities.view', null, OrganizationScopeService::SYSTEM_ONLY) !== null, 'Franchise actor must fail system-only authorization.');
bf013AuthAssert($service->authorize($partnerAgency, 'global_physical_identities.view', null, OrganizationScopeService::SYSTEM_ONLY) !== null, 'Partner Agency actor must fail system-only authorization.');

bf013AuthAssert($service->targetOrganization('organization_properties', 101) === 3, 'Property target Organization resolution failed.');
bf013AuthAssert($service->targetOrganization('owners', 201) === 3, 'Owner target Organization resolution failed.');
bf013AuthAssert($service->targetOrganization('ownerships', 301) === 3, 'Ownership target Organization resolution failed.');
bf013AuthAssert($service->targetOrganization('organization_properties', 999) === null, 'Missing Property must resolve to null.');
bf013AuthAssert($service->targetOrganization('owners', 999) === null, 'Missing Owner must resolve to null.');
bf013AuthAssert($service->targetOrganization('ownerships', 999) === null, 'Missing Ownership must resolve to null.');
bf013AuthAssertUnsupportedResource($service, 'global_physical_identities');
bf013AuthAssertUnsupportedResource($service, 'organization_propeties');

$migration = file_get_contents(dirname(__DIR__, 2) . '/database/migrations/017_add_bf013_permissions.sql');
if ($migration === false) { throw new RuntimeException('BF013 permission migration could not be read.'); }
preg_match_all("/\('([^']+)',\s*'[^']+',\s*'active'\)/", $migration, $matches);
$expectedCodes = [
    'properties.view', 'properties.manage', 'owners.view', 'owners.manage',
    'ownerships.view', 'ownerships.manage',
    'global_physical_identities.view', 'global_physical_identities.manage',
];
bf013AuthAssert($matches[1] === $expectedCodes, 'Permission migration must contain exactly the eight approved BF013 codes.');

echo "BF013 authorization scope tests passed.\n";
