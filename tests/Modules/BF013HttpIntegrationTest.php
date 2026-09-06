<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\Database\QueryBuilderInterface;
use App\Http\HeaderBag;
use App\Http\InputBag;
use App\Http\Request;
use App\Modules\Authentication\Repositories\AuthTokenRepository;
use App\Modules\Authentication\Services\AuthenticationService;
use App\Modules\GlobalPropertyIdentity\Controllers\GlobalPhysicalIdentityController;
use App\Modules\Owner\Controllers\OwnerController;
use App\Modules\Ownership\Controllers\OwnershipController;
use App\Modules\Property\Controllers\OrganizationPropertyController;
use App\Modules\User\Repositories\UserRepository;
use App\Responses\Response;
use App\Routing\Router;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

final class BF013HttpDatabase implements DatabaseConnectionInterface
{
    public function connection(): PDO { throw new RuntimeException('Direct PDO access is not expected.'); }
    public function beginTransaction(): void {}
    public function commit(): void {}
    public function rollback(): void {}
    public function transaction(callable $callback): mixed { return $callback($this); }
}

final class BF013HttpQueryBuilder implements QueryBuilderInterface
{
    /** @var array<string, array<int, array<string, mixed>>> */
    public array $tables;
    private string $tableName = '';
    private array $conditions = [];

    public function __construct()
    {
        $codes = ['properties.view', 'properties.manage', 'owners.view', 'owners.manage', 'ownerships.view', 'ownerships.manage', 'global_physical_identities.view', 'global_physical_identities.manage'];
        $permissions = $assignments = [];
        foreach ($codes as $index => $code) {
            $id = $index + 1;
            $permissions[$id] = ['id' => $id, 'code' => $code, 'status' => 'active'];
            foreach ([10, 20, 30] as $positionId) { $assignments[] = ['id' => count($assignments) + 1, 'position_id' => $positionId, 'permission_id' => $id]; }
        }
        $users = [
            1 => ['id' => 1, 'organization_id' => 1, 'position_id' => 10, 'status' => 'active'],
            2 => ['id' => 2, 'organization_id' => 1, 'position_id' => 11, 'status' => 'active'],
            3 => ['id' => 3, 'organization_id' => 2, 'position_id' => 20, 'status' => 'active'],
            4 => ['id' => 4, 'organization_id' => 3, 'position_id' => 30, 'status' => 'active'],
        ];
        $tokens = [];
        foreach (['system' => 1, 'system-no-capability' => 2, 'franchise' => 3, 'partner' => 4] as $token => $userId) {
            $tokens[] = ['id' => count($tokens) + 1, 'user_id' => $userId, 'token_hash' => hash('sha256', $token), 'expires_at' => '2999-01-01 00:00:00', 'revoked_at' => null];
        }
        $this->tables = [
            'organizations' => [
                1 => ['id' => 1, 'organization_type' => 'system', 'parent_organization_id' => null, 'status' => 'active'],
                2 => ['id' => 2, 'organization_type' => 'franchise', 'parent_organization_id' => 1, 'status' => 'active'],
                3 => ['id' => 3, 'organization_type' => 'partner_agency', 'parent_organization_id' => 2, 'status' => 'active'],
                4 => ['id' => 4, 'organization_type' => 'franchise', 'parent_organization_id' => 1, 'status' => 'active'],
                5 => ['id' => 5, 'organization_type' => 'partner_agency', 'parent_organization_id' => 4, 'status' => 'active'],
            ],
            'users' => $users,
            'auth_tokens' => $tokens,
            'positions' => [10 => ['id' => 10, 'organization_id' => 1, 'status' => 'active'], 11 => ['id' => 11, 'organization_id' => 1, 'status' => 'active'], 20 => ['id' => 20, 'organization_id' => 2, 'status' => 'active'], 30 => ['id' => 30, 'organization_id' => 3, 'status' => 'active']],
            'permissions' => $permissions,
            'position_permissions' => $assignments,
            'organization_properties' => [101 => ['id' => 101, 'organization_id' => 3, 'property_label' => 'Partner Property', 'status' => 'active'], 102 => ['id' => 102, 'organization_id' => 5, 'property_label' => 'Unrelated Property', 'status' => 'active'], 103 => ['id' => 103, 'organization_id' => 2, 'property_label' => 'Franchise Property', 'status' => 'active'], 104 => ['id' => 104, 'organization_id' => 3, 'property_label' => 'Second Partner Property', 'status' => 'active']],
            'owners' => [201 => ['id' => 201, 'organization_id' => 3, 'party_type' => 'individual', 'display_name' => 'Partner Owner', 'mobile_normalized' => '111', 'email_normalized' => 'owner@example.com', 'status' => 'active'], 202 => ['id' => 202, 'organization_id' => 2, 'party_type' => 'individual', 'display_name' => 'Franchise Owner', 'status' => 'active'], 203 => ['id' => 203, 'organization_id' => 3, 'party_type' => 'individual', 'display_name' => 'Second Partner Owner', 'status' => 'active']],
            'ownerships' => [301 => ['id' => 301, 'organization_property_id' => 101, 'organization_id' => 3, 'status' => 'current'], 302 => ['id' => 302, 'organization_property_id' => 103, 'organization_id' => 2, 'status' => 'current'], 303 => ['id' => 303, 'organization_property_id' => 104, 'organization_id' => 3, 'status' => 'current']],
            'ownership_parties' => [401 => ['id' => 401, 'ownership_id' => 301, 'owner_id' => 201, 'organization_id' => 3, 'share_percentage' => '100.0000'], 402 => ['id' => 402, 'ownership_id' => 303, 'owner_id' => 201, 'organization_id' => 3, 'share_percentage' => null]],
            'authorized_acting_owner_designations' => [501 => ['id' => 501, 'ownership_id' => 301, 'current_ownership_guard' => 301, 'ownership_party_id' => 401, 'basis_source' => 'agreement', 'started_at' => '2026-01-01 00:00:00', 'ended_at' => null]],
            'global_physical_property_identities' => [601 => ['id' => 601, 'ulid' => str_repeat('0', 26)]],
            'global_physical_identity_links' => [],
            'property_owner_lifecycle_history' => [],
            'franchises' => [], 'partner_agencies' => [],
        ];
    }

    public function table(string $table): self { $this->reset(); $this->tableName = $table; return $this; }
    public function select(array $columns = ['*']): self { return $this; }
    public function where(string $field, string $operator, mixed $value): self { $this->conditions[] = [$field, $operator, $value]; return $this; }
    public function orWhere(string $field, string $operator, mixed $value): self { return $this->where($field, $operator, $value); }
    public function whereIn(string $field, array $values): self { $this->conditions[] = [$field, 'IN', $values]; return $this; }
    public function orderBy(string $field, string $direction = 'ASC'): self { return $this; }
    public function limit(int $limit): self { return $this; }
    public function offset(int $offset): self { return $this; }
    public function forUpdate(): self { return $this; }
    public function first(): ?array { $rows = $this->matching(); $this->reset(); return $rows[0] ?? null; }
    public function get(): array { $rows = $this->matching(); $this->reset(); return $rows; }
    public function insert(array $data): int { $id = $this->tables[$this->tableName] === [] ? 1 : max(array_keys($this->tables[$this->tableName])) + 1; $data['id'] = $id; $this->tables[$this->tableName][$id] = $data; $this->reset(); return $id; }
    public function update(array $data): int { $ids = array_column($this->matching(), 'id'); foreach ($ids as $id) { $this->tables[$this->tableName][$id] = array_merge($this->tables[$this->tableName][$id], $data); } $this->reset(); return count($ids); }
    public function delete(): int { $ids = array_column($this->matching(), 'id'); foreach ($ids as $id) { unset($this->tables[$this->tableName][$id]); } $this->reset(); return count($ids); }

    private function matching(): array
    {
        return array_values(array_filter($this->tables[$this->tableName] ?? [], function (array $row): bool {
            foreach ($this->conditions as [$field, $operator, $value]) {
                $actual = $row[$field] ?? null;
                if ($operator === '=' && $actual != $value) { return false; }
                if ($operator === '!=' && $actual == $value) { return false; }
                if ($operator === 'IN' && ! in_array($actual, $value, true)) { return false; }
                if ($operator === 'LIKE' && stripos((string) $actual, trim((string) $value, '%')) === false) { return false; }
            }
            return true;
        }));
    }
    private function reset(): void { $this->tableName = ''; $this->conditions = []; }
}

function bf013HttpAssert(bool $condition, string $message): void { if (! $condition) { throw new RuntimeException($message); } }

/** @return array{status: int, payload: array<string, mixed>} */
function bf013HttpResult(Response $response): array
{
    $statusProperty = new ReflectionProperty(Response::class, 'statusCode');
    $payloadProperty = new ReflectionProperty(Response::class, 'payload');
    $statusProperty->setAccessible(true);
    $payloadProperty->setAccessible(true);
    $status = $statusProperty->getValue($response);
    $payload = $payloadProperty->getValue($response);
    return ['status' => $status, 'payload' => $payload];
}

function bf013HttpRequest(string $method, string $uri, ?string $token = null, array $query = [], array $json = []): Request
{
    $headers = $token === null ? [] : ['Authorization' => "Bearer {$token}"];
    return new Request(new InputBag($query), new InputBag([]), new InputBag($json), new HeaderBag($headers), new InputBag([]), new InputBag([]), [], $method, $uri);
}

function bf013HttpDispatch(Router $router, string $method, string $uri, ?string $token = null, array $query = [], array $json = []): array
{
    return bf013HttpResult($router->dispatch($method, $uri, bf013HttpRequest($method, $uri, $token, $query, $json)));
}

$database = new BF013HttpDatabase();
$query = new BF013HttpQueryBuilder();
$container = new Container();
$container->instance(DatabaseConnectionInterface::class, $database);
$container->instance(QueryBuilderInterface::class, $query);
$container->instance(UlidGeneratorInterface::class, new class implements UlidGeneratorInterface {
    private int $value = 0;
    public function generate(): string { return str_pad((string) ++$this->value, 26, '0', STR_PAD_LEFT); }
});
$users = new UserRepository($database, $query);
$container->instance(AuthenticationService::class, new AuthenticationService($users, new AuthTokenRepository($database, $query), ['app' => ['auth' => ['token_ttl' => 3600]]]));
$router = new Router();
$routes = require dirname(__DIR__, 2) . '/routes/api.php';
$routes($router, $container, ['app' => ['version' => 'test']]);

bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/does-not-exist')['status'] === 404, 'Unknown route behavior changed.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/organization-properties')['status'] === 401, 'BF013 routes must require authentication.');
$protectedRoutes = [
    ['GET', '/organization-properties'], ['POST', '/organization-properties'],
    ['GET', '/organization-properties/101'], ['PUT', '/organization-properties/101'],
    ['DELETE', '/organization-properties/101'], ['POST', '/organization-properties/101/reactivate'],
    ['GET', '/owners'], ['POST', '/owners'], ['GET', '/owners/201'], ['PUT', '/owners/201'],
    ['DELETE', '/owners/201'], ['POST', '/owners/201/reactivate'],
    ['POST', '/organization-properties/101/ownerships'],
    ['GET', '/organization-properties/101/ownerships/current'],
    ['GET', '/organization-properties/101/ownerships'], ['GET', '/ownerships/301'],
    ['POST', '/ownerships/301/close'], ['GET', '/ownerships/301/parties'],
    ['POST', '/ownerships/301/parties'], ['PUT', '/ownerships/301/parties/401'],
    ['DELETE', '/ownerships/301/parties/401'], ['GET', '/ownerships/301/acting-owner'],
    ['POST', '/ownerships/301/acting-owner'], ['PUT', '/ownerships/301/acting-owner'],
    ['DELETE', '/ownerships/301/acting-owner'], ['GET', '/ownerships/301/acting-owner/history'],
    ['GET', '/global-physical-identities'], ['POST', '/global-physical-identities'],
    ['GET', '/global-physical-identities/601'], ['GET', '/global-physical-identities/601/representations'],
    ['GET', '/organization-properties/101/global-identity-link'],
    ['POST', '/organization-properties/101/global-identity-link'],
    ['PUT', '/organization-properties/101/global-identity-link'],
    ['DELETE', '/organization-properties/101/global-identity-link'],
    ['GET', '/organization-properties/101/global-identity-link/history'],
];
foreach ($protectedRoutes as [$method, $uri]) {
    bf013HttpAssert(bf013HttpDispatch($router, $method, $uri)['status'] === 401, "{$method} {$uri} was not registered as a protected route.");
}

$result = bf013HttpDispatch($router, 'GET', '/organization-properties/101', 'partner');
bf013HttpAssert($result['status'] === 200, 'Partner Agency must access its Property.');
foreach (['owners', 'ownership', 'acting_owner', 'global_physical_identity'] as $field) { bf013HttpAssert(! array_key_exists($field, $result['payload']['data']), "Property response leaked {$field}."); }
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/organization-properties/101', 'franchise')['status'] === 200, 'Franchise must access direct-child Property shell.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/organization-properties/102', 'franchise')['status'] === 403, 'Franchise must not access unrelated Property.');
$listRequest = bf013HttpRequest('GET', '/organization-properties', 'franchise');
$result = bf013HttpResult($router->dispatch('GET', '/organization-properties', $listRequest));
bf013HttpAssert($result['status'] === 200 && count($result['payload']['data']) === 3, 'Property list must use hierarchy scope.');
bf013HttpAssert($listRequest->attribute('auth.target.organization_id') === null, 'No-selector list must not create a trusted single target.');
bf013HttpAssert($listRequest->attribute('auth.scope.organization_ids') === [2, 3], 'No-selector list must retain the authorized hierarchy scope.');
$authorizedRequest = bf013HttpRequest('POST', '/organization-properties', 'franchise', [], ['organization_id' => 3, 'property_label' => 'Created']);
$result = bf013HttpResult($router->dispatch('POST', '/organization-properties', $authorizedRequest));
bf013HttpAssert($result['status'] === 201 && $result['payload']['data']['organization_id'] === 3, 'Property create must use authorized requested target.');
bf013HttpAssert($authorizedRequest->attribute('auth.target.organization_id') === 3, 'Authorized candidate must become the trusted target before controller execution.');
$result = bf013HttpDispatch($router, 'POST', '/organization-properties', 'partner', [], ['property_label' => 'Own Property']);
bf013HttpAssert($result['status'] === 201 && $result['payload']['data']['organization_id'] === 3, 'Omitted Property target must default to the actor Organization.');
$deniedRequest = bf013HttpRequest('POST', '/organization-properties', 'franchise', [], ['organization_id' => 5, 'property_label' => 'Denied']);
bf013HttpAssert(bf013HttpResult($router->dispatch('POST', '/organization-properties', $deniedRequest))['status'] === 403, 'Unauthorized requested Organization must be denied.');
bf013HttpAssert($deniedRequest->attribute('auth.target.organization_id') === null, 'Denied candidate must not become a trusted target.');
$invalidRequest = bf013HttpRequest('POST', '/organization-properties', 'franchise', [], ['organization_id' => 'bad', 'property_label' => 'Invalid']);
bf013HttpAssert(bf013HttpResult($router->dispatch('POST', '/organization-properties', $invalidRequest))['status'] === 422, 'Invalid requested Organization must be rejected.');
bf013HttpAssert($invalidRequest->attribute('auth.target.organization_id') === null, 'Invalid candidate must not become a trusted target.');
$missingRequest = bf013HttpRequest('GET', '/organization-properties/999', 'system');
bf013HttpAssert(bf013HttpResult($router->dispatch('GET', '/organization-properties/999', $missingRequest))['status'] === 404, 'Missing supported Property target must return 404.');
bf013HttpAssert($missingRequest->attribute('auth.target.organization_id') === null, 'Missing resource must not create a trusted target.');

bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/owners/201', 'partner')['status'] === 200, 'Partner Agency must access its Owner.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/owners/201', 'franchise')['status'] === 403, 'Franchise must not access child Partner Agency Owner.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/owners/201', 'system')['status'] === 200, 'System must access an authorized Owner target.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/owners/202', 'partner')['status'] === 403, 'Partner Agency must not access cross-Organization Owner.');
bf013HttpAssert(bf013HttpDispatch($router, 'POST', '/owners', 'partner', [], ['organization_id' => 2, 'party_type' => 'individual', 'display_name' => 'Denied Owner'])['status'] === 403, 'Non-System Owner create must not accept another Organization.');
$result = bf013HttpDispatch($router, 'POST', '/owners', 'system', [], ['organization_id' => 3, 'party_type' => 'individual', 'display_name' => 'System Created Owner']);
bf013HttpAssert($result['status'] === 201 && $result['payload']['data']['organization_id'] === 3, 'System Owner create must accept an authorized explicit target.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/owners', 'partner', ['display_name' => 'Partner', 'email' => 'owner@example.com'])['status'] === 422, 'Multiple Owner selectors must be rejected.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/owners', 'partner', ['display_name' => '   '])['status'] === 422, 'Blank Owner search must surface Service validation as 422.');
$result = bf013HttpDispatch($router, 'GET', '/owners', 'partner', ['display_name' => 'Partner']);
bf013HttpAssert(
    $result['status'] === 200
        && $result['payload']['data'] !== []
        && array_unique(array_column($result['payload']['data'], 'organization_id')) === [3],
    'Owner search must remain within one private Organization.'
);
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/owners', 'system', ['display_name' => 'Owner'])['status'] === 422, 'System Owner search must require an explicit Organization.');

bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/ownerships/301', 'partner')['status'] === 200, 'Partner Agency must access its Ownership.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/ownerships/301', 'franchise')['status'] === 403, 'Franchise must not access child private Ownership.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/organization-properties/101/ownerships/current', 'partner')['status'] === 200, 'Property-nested Ownership authorization failed.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/ownerships/301/parties', 'partner')['status'] === 200, 'Party route must authorize through parent Ownership.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/ownerships/301/parties', 'franchise')['status'] === 403, 'Party route exposed child private Ownership.');
bf013HttpAssert(bf013HttpDispatch($router, 'POST', '/ownerships/303/parties', 'partner', [], ['owner_id' => 203, 'share_percentage' => '40'])['status'] === 200, 'Successful Party add must return 200.');
bf013HttpAssert(bf013HttpDispatch($router, 'POST', '/ownerships/303/acting-owner', 'partner', [], ['ownership_party_id' => 402, 'basis_source' => 'agreement'])['status'] === 200, 'Successful Acting Owner designation must return 200.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/ownerships/301/acting-owner', 'partner')['status'] === 200, 'Acting Owner route must authorize through parent Ownership.');
bf013HttpAssert(bf013HttpDispatch($router, 'POST', '/organization-properties/101/ownerships', 'partner', [], ['parties' => 'invalid'])['status'] === 422, 'Malformed Ownership payload must return 422.');

bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/global-physical-identities', 'system')['status'] === 200, 'System with capability must access Global Identities.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/global-physical-identities', 'system-no-capability')['status'] === 403, 'System without capability must be denied.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/global-physical-identities', 'franchise')['status'] === 403, 'Franchise with capability must fail system-only authorization.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/global-physical-identities', 'partner')['status'] === 403, 'Partner Agency with capability must fail system-only authorization.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/global-physical-identities/601/representations', 'system')['status'] === 200, 'Existing Global Identity representations must return 200.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/global-physical-identities/999/representations', 'system')['status'] === 404, 'Missing Global Identity representations must return 404.');
bf013HttpAssert(bf013HttpDispatch($router, 'GET', '/organization-properties/999/global-identity-link', 'system')['status'] === 404, 'Global link route must validate parent Property existence.');

$routeSource = file_get_contents(dirname(__DIR__, 2) . '/routes/api.php');
foreach (['/organization-properties/{id}/reactivate', '/owners/{id}/reactivate', '/ownerships/{id}/close', '/ownerships/{id}/acting-owner/history', '/global-physical-identities/{id}/representations', '/organization-properties/{propertyId}/global-identity-link/history'] as $route) {
    bf013HttpAssert(is_string($routeSource) && str_contains($routeSource, $route), "Expected route {$route} was not registered.");
}
foreach ([OrganizationPropertyController::class, OwnerController::class, OwnershipController::class, GlobalPhysicalIdentityController::class] as $controller) {
    bf013HttpAssert($container->make($controller) instanceof $controller, "Container could not resolve {$controller}.");
}

echo "BF013 HTTP integration tests passed.\n";
