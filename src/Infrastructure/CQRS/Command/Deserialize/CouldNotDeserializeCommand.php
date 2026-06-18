<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Deserialize;

final class CouldNotDeserializeCommand extends \RuntimeException
{
    public static function invalidPayload(): self
    {
        return new self('Could not deserialize command, expected a JSON object with a "commandName" and "payload".');
    }

    public static function unknownCommand(string $commandName): self
    {
        return new self(sprintf('Could not deserialize command, "%s" is not a known command.', $commandName));
    }

    public static function notDeserializable(string $commandName): self
    {
        return new self(sprintf('Could not deserialize command, "%s" is not allowed to be dispatched from a request.', $commandName));
    }
}
