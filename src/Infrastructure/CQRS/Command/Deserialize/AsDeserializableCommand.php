<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Deserialize;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsDeserializableCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
