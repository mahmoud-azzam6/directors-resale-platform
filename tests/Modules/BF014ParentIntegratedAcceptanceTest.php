<?php

declare(strict_types=1);

$tests = [
    'BF014SchemaAcceptanceTest.php',
    'BF014RepositoryDatabaseAcceptanceTest.php',
    'PropertyCatalogServiceDatabaseAcceptanceTest.php',
    'UnitTypeConfigurationC4Test.php',
    'GeographicLocationServiceD1Test.php',
    'DevelopmentCatalogServiceD2Test.php',
    'BF014SeedIntegratedAcceptanceTest.php',
    'BF014DynamicFormProjectionIntegratedAcceptanceTest.php',
    'BF014HttpIntegratedAcceptanceTest.php',
    'BF013AuthorizationTest.php',
];

foreach ($tests as $test) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $test;
    if (! is_file($path)) {
        throw new RuntimeException('Missing parent acceptance dependency: ' . $test);
    }
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($path), $status);
    if ($status !== 0) {
        throw new RuntimeException('Parent integrated acceptance dependency failed: ' . $test);
    }
}

echo "BF014 parent integrated MariaDB acceptance: PASS\n";