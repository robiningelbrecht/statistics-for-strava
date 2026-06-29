<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Deserialize;

use App\Infrastructure\CQRS\Command\Command;

interface DeserializableCommand extends Command
{
    public static function getCommandName(): string;

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self;
}
