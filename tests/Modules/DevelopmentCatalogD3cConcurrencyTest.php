<?php

declare(strict_types=1);

use App\Core\Container;
use App\Modules\Property\Services\DevelopmentCatalogService;

require __DIR__ . '/CatalogD3ConcurrencyHarness.php';

d3Run('d3c', __FILE__, function (Container $container, string $suffix): array {
    $service = $container->make(DevelopmentCatalogService::class);
    $project = $service->createProject(d3Data('PROJECT-' . $suffix));
    $code = 'PHASE-' . $suffix;
    return [
        'parent_table' => 'projects', 'parent_id' => $project['id'],
        'child_table' => 'project_phases', 'child_foreign_key' => 'project_id', 'child_code' => $code,
        'deactivate' => ['method' => 'deactivateProject', 'args' => [$project['id']]],
        'child' => ['method' => 'createProjectPhase', 'args' => [$project['id'], d3Data($code)]],
        'blocked_error' => 'PROJECT_HAS_ACTIVE_PHASES',
    ];
});
