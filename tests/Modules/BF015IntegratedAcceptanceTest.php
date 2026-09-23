<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$routes = (string) file_get_contents($root . '/routes/api.php');
$contract = (string) file_get_contents($root . '/docs/sprints/BF015-property-profile-persistence-bridge.md');
foreach (['/organization-properties/{id}/profile', 'properties.view', 'properties.manage'] as $required) {
    if (! str_contains($routes, $required)) throw new RuntimeException('Missing BF015 HTTP contract: ' . $required);
}
foreach (['/property-wizard', '/organization-properties/full', '/organization-properties/{id}/complete'] as $forbidden) {
    if (str_contains($routes, $forbidden)) throw new RuntimeException('Unexpected BF015 wizard route: ' . $forbidden);
}
if (! str_contains($contract, 'property_measurements') || ! str_contains($contract, 'property_attribute_values')) throw new RuntimeException('BF015 persistence contract missing.');
$tests = [
    'BF015SchemaAcceptanceTest.php',
    'BF015RepositoryDatabaseAcceptanceTest.php',
    'BF015PropertyProfileServiceTest.php',
    'BF015HttpIntegrationTest.php',
    'BF013DatabaseAcceptanceTest.php',
    'BF013HttpIntegrationTest.php',
    'BF013AuthorizationTest.php',
    'BF014DynamicFormProjectionIntegratedAcceptanceTest.php',
    'BF014SeedIntegratedAcceptanceTest.php',
    'BF014HttpIntegratedAcceptanceTest.php',
];
foreach ($tests as $test) {
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/' . $test), $status);
    if ($status !== 0) throw new RuntimeException('Integrated dependency failed: ' . $test);
}
echo "BF015 integrated MariaDB acceptance: PASS\n";