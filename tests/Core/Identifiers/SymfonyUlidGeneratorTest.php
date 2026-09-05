<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Contracts\UlidGeneratorInterface;
use App\Core\Identifiers\SymfonyUlidGenerator;
use App\Providers\AppServiceProvider;
use Symfony\Component\Uid\Ulid;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

function assertUlidFoundation(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$generator = new SymfonyUlidGenerator();
$first = $generator->generate();
$second = $generator->generate();

assertUlidFoundation(is_string($first), 'Generated ULID must be a string.');
assertUlidFoundation(strlen($first) === 26, 'Generated ULID must contain exactly 26 characters.');
assertUlidFoundation(
    preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $first) === 1,
    'Generated ULID must use the canonical Crockford Base32 character set.'
);
assertUlidFoundation(Ulid::isValid($first), 'Symfony must accept the generated ULID.');
assertUlidFoundation((string) Ulid::fromString($first) === $first, 'Generated ULID must round-trip through Symfony.');
assertUlidFoundation($first !== $second, 'Sequentially generated ULIDs must be distinct.');

$container = new Container();
$provider = new AppServiceProvider($container, [
    'app' => [
        'name' => 'ULID Test',
        'log' => ['path' => 'php://stderr', 'level' => 100],
    ],
    'database' => [],
]);
$provider->register();

$resolved = $container->make(UlidGeneratorInterface::class);
$resolvedAgain = $container->make(UlidGeneratorInterface::class);

assertUlidFoundation($resolved instanceof SymfonyUlidGenerator, 'Container must resolve the Symfony ULID implementation.');
assertUlidFoundation($resolved === $resolvedAgain, 'ULID generator binding must be a singleton.');

echo "ULID foundation tests passed.\n";
