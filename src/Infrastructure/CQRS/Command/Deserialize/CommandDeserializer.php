<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Deserialize;

use App\Infrastructure\Serialization\Json;

final readonly class CommandDeserializer
{
    public function __construct(
        private DeserializableCommandRegistry $registry,
    ) {
    }

    public function deserialize(string $json): DeserializableCommand
    {
        $decoded = Json::decode($json);

        if (!is_array($decoded)
            || !isset($decoded['commandName'])
            || !is_string($decoded['commandName'])
            || !isset($decoded['payload'])
            || !is_array($decoded['payload'])) {
            throw CouldNotDeserializeCommand::invalidPayload('Expected a JSON object with a "commandName" and "payload".');
        }

        $commandClass = $this->registry->resolve($decoded['commandName']);

        return $commandClass::fromPayload($decoded['payload']);
    }
}
