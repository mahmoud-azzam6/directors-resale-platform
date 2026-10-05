<?php

declare(strict_types=1);

// Reuse the delivered real-MariaDB contracts rather than duplicate their fixtures.
// Administrative/profile and image assertions exercise the same BF015 aggregate;
// the ownership dependency additionally verifies the existing BF013 behavior.
$tests = [
    'BF016OrganizationBasicProfileHttpIntegrationTest.php',
    'BF016PropertyAdministrativeDetailsHttpIntegrationTest.php',
    'BF016PrimaryImageHttpIntegrationTest.php',
    'BF013HttpIntegrationTest.php',
    'BF015HttpIntegrationTest.php',
];

foreach ($tests as $test) {
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/' . $test), $status);
    if ($status !== 0) {
        throw new RuntimeException('BF016 integrated dependency failed: ' . $test);
    }
}

echo "BF016 integrated MariaDB acceptance: PASS\n";
