<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Deserialize;

final readonly class DeserializableCommandRegistry
{
    /**
     * @param array<string, class-string<DeserializableCommand>> $commandsByName
     */
    public function __construct(
        private array $commandsByName = [],
    ) {
    }

    /**
     * @return class-string<DeserializableCommand>
     */
    public function resolve(string $name): string
    {
        return $this->commandsByName[$name] ?? throw CouldNotDeserializeCommand::unknownCommand($name);
    }
}
