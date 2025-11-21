<?php

namespace App\Tests\Infrastructure\CQRS\Command\Bus;

use App\Infrastructure\CQRS\CanNotRegisterCQRSHandler;
use App\Infrastructure\CQRS\Command\Bus\InMemoryCommandBus;
use App\Tests\Infrastructure\CQRS\Command\Bus\RunAnOperation\RunAnOperation;
use App\Tests\Infrastructure\CQRS\Command\Bus\RunAnOperation\RunAnOperationCommandHandler;
use App\Tests\Infrastructure\CQRS\Command\Bus\RunAnOperationCommand\RunAnOperationCommandCommandHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;

class InMemoryCommandBusTest extends KernelTestCase
{
    public function testDispatch(): void
    {
        $commandBus = new InMemoryCommandBus([
            new RunAnOperationCommandHandler(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This is a test command and it is called');

        $commandBus->dispatch(new RunAnOperation('test'));
    }

    public function testDispatchWhenNotRegistered(): void
    {
        $commandBus = new InMemoryCommandBus([]);

        $this->expectException(NoHandlerForMessageException::class);
        $this->expectExceptionMessage(RunAnOperation::class);

        $commandBus->dispatch(new RunAnOperation('test'));
    }

    public function testDispatchWithoutCorrespondingCommand(): void
    {
        $this->expectException(CanNotRegisterCQRSHandler::class);
        $this->expectExceptionMessage('No corresponding object for CommandHandler "App\Tests\Infrastructure\CQRS\Command\Bus\RunOperationWithoutACommandCommandHandler" found. Expected namespace: App\Tests\Infrastructure\CQRS\Command\Bus\RunOperationWithoutACommand');

        $commandBus = new InMemoryCommandBus([
            new RunOperationWithoutACommandCommandHandler(),
        ]);
        $commandBus->dispatch(new RunAnOperation('test'));
    }

    public function testDispatchWithInvalidCommandName(): void
    {
        $this->expectException(CanNotRegisterCQRSHandler::class);
        $this->expectExceptionMessage('Object name cannot end with "Command"');

        $commandBus = new InMemoryCommandBus([
            new RunAnOperationCommandCommandHandler(),
        ]);
        $commandBus->dispatch(new RunAnOperation('test'));
    }

    public function testDispatchWithInvalidCommandHandlerName(): void
    {
        $this->expectException(CanNotRegisterCQRSHandler::class);
        $this->expectExceptionMessage('Fqcn "App\Tests\Infrastructure\CQRS\Command\Bus\RunOperationWithInvalidNameHandler" does not end with "CommandHandler"');

        $commandBus = new InMemoryCommandBus([
            new RunOperationWithInvalidNameHandler(),
        ]);
        $commandBus->dispatch(new RunAnOperation('test'));
    }
}
