<?php

declare(strict_types=1);

if ($argc !== 2 || trim($argv[1]) === '') {
    fwrite(STDERR, "Usage: php scripts/set-user-password.php user@example.com\n");
    exit(1);
}

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$repository = $app->container()->make(\App\Modules\User\Repositories\UserRepository::class);
$user = $repository->findByEmail(trim($argv[1]));

if ($user === null) {
    fwrite(STDERR, "User not found.\n");
    exit(1);
}

fwrite(STDOUT, "Password: ");
$password = rtrim((string) fgets(STDIN), "\r\n");

if ($password === '') {
    fwrite(STDERR, "Password cannot be empty.\n");
    exit(1);
}

$repository->setPasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
fwrite(STDOUT, "Password credential updated.\n");