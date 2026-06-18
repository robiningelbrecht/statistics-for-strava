<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Deserialize;

final readonly class DeserializableCommandRegistry
{
    /**
     * @param array<string, class-string<DeserializableCommand>> $commandsById
     */
    public function __construct(
        private array $commandsById = [],
    ) {
    }

    /**
     * @return class-string<DeserializableCommand>
     */
    public function resolve(string $id): string
    {
        return $this->commandsById[$id] ?? throw CouldNotDeserializeCommand::unknownCommand($id);
    }
}
