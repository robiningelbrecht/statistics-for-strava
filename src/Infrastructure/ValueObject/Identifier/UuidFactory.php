<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Identifier;

interface UuidFactory
{
    public function random(): string;
}
