<?php

declare(strict_types=1);

namespace App\Core\Identifiers;

use App\Core\Contracts\UlidGeneratorInterface;
use Symfony\Component\Uid\Ulid;

final class SymfonyUlidGenerator implements UlidGeneratorInterface
{
    public function generate(): string
    {
        return (string) new Ulid();
    }
}
