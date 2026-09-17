<?php

declare(strict_types=1);

use App\Core\Container;
use App\Modules\Property\Services\DevelopmentCatalogService;

require __DIR__ . '/CatalogD3ConcurrencyHarness.php';

d3Run('d3d', __FILE__, function (Container $container, string $suffix): array {
    $service = $container->make(DevelopmentCatalogService::class);
    $project = $service->createProject(d3Data('PROJECT-' . $suffix));
    $phase = $service->createProjectPhase($project['id'], d3Data('PHASE-' . $suffix));
    $service->deactivateProjectPhase($project['id'], $phase['id']);
    return [
        'parent_table' => 'projects', 'parent_id' => $project['id'],
        'child_table' => 'project_phases', 'child_foreign_key' => 'project_id', 'child_id' => $phase['id'],
        'deactivate' => ['method' => 'deactivateProject', 'args' => [$project['id']]],
        'child' => ['method' => 'reactivateProjectPhase', 'args' => [$project['id'], $phase['id']]],
        'blocked_error' => 'PROJECT_HAS_ACTIVE_PHASES',
    ];
});
