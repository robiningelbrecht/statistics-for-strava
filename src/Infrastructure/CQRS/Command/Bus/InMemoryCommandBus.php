<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Bus;

use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\CQRS\HandlerBuilder;
use App\Infrastructure\CQRS\HandlerBuilderType;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final readonly class InMemoryCommandBus implements CommandBus
{
    private MessageBusInterface $bus;

    /**
     * @param iterable<CommandHandler> $commandHandlers
     */
    public function __construct(
        #[AutowireIterator('app.command_handler')]
        iterable $commandHandlers,
    ) {
        $this->bus = new MessageBus([
            new HandleMessageMiddleware(
                new HandlersLocator(
                    new HandlerBuilder(HandlerBuilderType::COMMAND_HANDLER)
                        ->fromCallables($commandHandlers),
                ),
            ),
        ]);
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
