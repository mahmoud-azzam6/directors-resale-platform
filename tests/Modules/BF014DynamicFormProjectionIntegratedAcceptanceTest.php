<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$tests = [
    'BF014ProjectionReadModelSupportTest.php',
    'BF014PropertyFormProjectionServiceTest.php',
    'BF014CoreFormProjectionServiceTest.php',
    'BF014DynamicFormProjectionHttpTest.php',
];

foreach ($tests as $test) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $test;
    if (! is_file($path)) {
        throw new RuntimeException('Missing integrated acceptance dependency: ' . $test);
    }
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($path), $status);
    if ($status !== 0) {
        throw new RuntimeException('Integrated acceptance dependency failed: ' . $test);
    }
}

echo "BF014.6E dynamic form projection integrated MariaDB acceptance: PASS\n";