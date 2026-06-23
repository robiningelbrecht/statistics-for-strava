<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Deserialize;

final class CouldNotDeserializeCommand extends \RuntimeException
{
    public static function invalidPayload(string $message): self
    {
        return new self($message);
    }

    public static function unknownCommand(string $commandName): self
    {
        return new self(sprintf('Could not deserialize command, "%s" is not a known command.', $commandName));
    }
}
