<?php

declare(strict_types=1);

use App\Core\Container;
use App\Modules\Property\Services\DevelopmentCatalogService;

require __DIR__ . '/CatalogD3ConcurrencyHarness.php';

d3Run('d3b', __FILE__, function (Container $container, string $suffix): array {
    $service = $container->make(DevelopmentCatalogService::class);
    $developer = $service->createDeveloper(d3Data('DEVELOPER-' . $suffix));
    $code = 'PROJECT-' . $suffix;
    return [
        'parent_table' => 'developers', 'parent_id' => $developer['id'],
        'child_table' => 'projects', 'child_foreign_key' => 'developer_id', 'child_code' => $code,
        'deactivate' => ['method' => 'deactivateDeveloper', 'args' => [$developer['id']]],
        'child' => ['method' => 'createProject', 'args' => [d3Data($code, ['developer_id' => $developer['id']])]],
        'blocked_error' => 'DEVELOPER_HAS_ACTIVE_PROJECTS',
    ];
});
