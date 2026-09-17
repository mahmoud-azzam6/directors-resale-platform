<?php

declare(strict_types=1);

use App\Core\Container;
use App\Modules\Property\Services\GeographicLocationService;

require __DIR__ . '/CatalogD3ConcurrencyHarness.php';

d3Run('d3a', __FILE__, function (Container $container, string $suffix): array {
    $service = $container->make(GeographicLocationService::class);
    $root = $service->createLocation(d3Data('ROOT-' . $suffix, ['location_type' => 'COUNTRY']));
    $parent = $service->createLocation(d3Data('PARENT-' . $suffix, ['location_type' => 'CITY', 'parent_id' => $root['id']]));
    $code = 'DESCENDANT-' . $suffix;
    return [
        'parent_table' => 'geographic_locations', 'parent_id' => $parent['id'],
        'child_table' => 'geographic_locations', 'child_foreign_key' => 'parent_id', 'child_code' => $code,
        'deactivate' => ['method' => 'deactivateLocation', 'args' => [$parent['id']]],
        'child' => ['method' => 'createLocation', 'args' => [d3Data($code, ['location_type' => 'DISTRICT', 'parent_id' => $parent['id']])]],
        'blocked_error' => 'LOCATION_HAS_ACTIVE_DESCENDANTS',
        'verify' => function (PDO $pdo) use ($root): void {
            d3Assert(d3Scalar($pdo, 'SELECT status FROM geographic_locations WHERE id = ?', [$root['id']]) === 'active', 'Country lifecycle changed');
        },
    ];
});
