<?php

declare(strict_types=1);

namespace App\Core\Contracts;

interface UlidGeneratorInterface
{
    public function generate(): string;
}
