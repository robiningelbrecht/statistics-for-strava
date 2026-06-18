<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Deserialize;

use App\Infrastructure\Serialization\Json;

final readonly class CommandDeserializer
{
    public function deserialize(string $json): DeserializableCommand
    {
        $decoded = Json::decode($json);

        if (!is_array($decoded)
            || !isset($decoded['commandName'])
            || !is_string($decoded['commandName'])
            || !isset($decoded['payload'])
            || !is_array($decoded['payload'])) {
            throw CouldNotDeserializeCommand::invalidPayload();
        }

        /** @var class-string $commandName */
        $commandName = $decoded['commandName'];
        if (!class_exists($commandName)) {
            throw CouldNotDeserializeCommand::unknownCommand($commandName);
        }

        if (!is_a($commandName, DeserializableCommand::class, true)) {
            throw CouldNotDeserializeCommand::notDeserializable($commandName);
        }

        return $commandName::fromPayload($decoded['payload']);
    }
}
