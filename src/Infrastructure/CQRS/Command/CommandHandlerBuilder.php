<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command;

use App\Infrastructure\CQRS\Command\Bus\CanNotRegisterCommandHandler;

final readonly class CommandHandlerBuilder
{
    public const string COMMAND_HANDLER_SUFFIX = 'CommandHandler';

    /**
     * @param iterable<CommandHandler> $commandHandlers
     *
     * @return array<string, non-empty-list<\Closure>>
     */
    public function fromCallables(iterable $commandHandlers): array
    {
        $registeredCommandHandlers = [];

        foreach ($commandHandlers as $commandHandler) {
            $this->guardThatFqcnEndsInCommandHandler($commandHandler::class);
            $this->guardThatThereIsACorrespondingCommand($commandHandler);

            $commandFqcn = str_replace(self::COMMAND_HANDLER_SUFFIX, '', $commandHandler::class);
            $registeredCommandHandlers[$commandFqcn][] = $commandHandler->handle(...);
        }

        return $registeredCommandHandlers;
    }

    private function guardThatFqcnEndsInCommandHandler(string $fqcn): void
    {
        if (str_ends_with($fqcn, self::COMMAND_HANDLER_SUFFIX)) {
            return;
        }

        throw new CanNotRegisterCommandHandler(sprintf('Fqcn "%s" does not end with "CommandHandler"', $fqcn));
    }

    private function guardThatThereIsACorrespondingCommand(CommandHandler $commandHandler): void
    {
        $commandFqcn = str_replace(self::COMMAND_HANDLER_SUFFIX, '', $commandHandler::class);
        if (!class_exists($commandFqcn)) {
            throw new CanNotRegisterCommandHandler(sprintf('No corresponding command for commandHandler "%s" found', $commandHandler::class));
        }
        if (str_ends_with($commandFqcn, 'Command')) {
            throw new CanNotRegisterCommandHandler('Command name cannot end with "command"');
        }
    }
}
