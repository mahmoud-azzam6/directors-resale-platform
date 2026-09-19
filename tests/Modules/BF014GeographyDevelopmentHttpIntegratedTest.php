<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$routes = (string) file_get_contents($root . '/routes/api.php');
$geography = (string) file_get_contents($root . '/app/Modules/Property/Controllers/GeographicLocationController.php');
$development = (string) file_get_contents($root . '/app/Modules/Property/Controllers/DevelopmentCatalogController.php');
foreach (['/geographic-locations', '/developers', '/projects', '/project-phases'] as $route) {
    if (!str_contains($routes, $route)) { throw new RuntimeException("Missing $route"); }
}
foreach ([$geography, $development] as $controller) {
    if (str_contains($controller, 'Repository') || str_contains($controller, 'transaction') || str_contains($controller, 'ForUpdate')) { throw new RuntimeException('Controller boundary violation'); }
}
foreach (['BF014GeographyHttpTest.php', 'BF014DevelopmentHttpTest.php'] as $test) {
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/' . $test), $status);
    if ($status !== 0) { throw new RuntimeException("Integrated prerequisite failed: $test"); }
}
echo "BF014 Geography + Development HTTP integrated: PASS\n";
