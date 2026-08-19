<?php

declare(strict_types=1);

if ($argc !== 2 || ! ctype_digit($argv[1]) || (int) $argv[1] <= 0) {
    fwrite(STDERR, "Usage: php scripts/bootstrap-position-permissions.php POSITION_ID\n");
    exit(1);
}

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$service = $app->container()->make(\App\Modules\Permission\Services\PositionPermissionService::class);
$permissionRepository = $app->container()->make(\App\Modules\Permission\Repositories\PermissionRepository::class);
$codes = array_map(
    static fn (array $permission): string => (string) $permission['code'],
    $permissionRepository->allActive()
);
$service->replace((int) $argv[1], $codes);
fwrite(STDOUT, "Position permissions bootstrapped.\n");