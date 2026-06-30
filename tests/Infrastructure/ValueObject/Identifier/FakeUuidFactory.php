<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\ValueObject\Identifier;

use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use Ramsey\Uuid\Uuid;

class FakeUuidFactory implements UuidFactory
{
    public function random(): string
    {
        return Uuid::fromString('0025176c-5652-11ee-923d-02424dd627d5')->toString();
    }
}
