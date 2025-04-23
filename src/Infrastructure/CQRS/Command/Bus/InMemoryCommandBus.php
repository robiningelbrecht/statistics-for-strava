<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Bus;

use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\CQRS\Command\CommandHandlerBuilder;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final readonly class InMemoryCommandBus implements CommandBus
{
    private MessageBus $bus;
    /** @var string[] */
    private array $commands;

    /**
     * @param iterable<CommandHandler> $commandHandlers
     */
    public function __construct(
        iterable $commandHandlers,
    ) {
        $this->bus = new MessageBus([
            new HandleMessageMiddleware(
                new HandlersLocator(
                    new CommandHandlerBuilder()->fromCallables($commandHandlers),
                ),
            ),
        ]);

        $this->commands = array_map(
            static fn (CommandHandler $commandHandler) => str_replace(CommandHandlerBuilder::COMMAND_HANDLER_SUFFIX, '', $commandHandler::class),
            iterator_to_array($commandHandlers)
        );
    }

    /**
     * @return string[]
     */
    public function getAvailableCommands(): array
    {
        return $this->commands;
    }

    public function dispatch(Command $command): void
    {
        try {
            $this->bus->dispatch($command);
        } catch (NoHandlerForMessageException) {
            throw new \InvalidArgumentException(sprintf('The command has not a valid handler: %s', $command::class));
        } catch (HandlerFailedException $e) {
            if (!is_null($e->getPrevious())) {
                throw $e->getPrevious();
            }
        }
    }
}
