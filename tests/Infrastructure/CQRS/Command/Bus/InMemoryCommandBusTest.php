<?php

namespace App\Tests\Infrastructure\CQRS\Command\Bus;

use App\Infrastructure\CQRS\Command\Bus\CanNotRegisterCommandHandler;
use App\Infrastructure\CQRS\Command\Bus\InMemoryCommandBus;
use App\Tests\Infrastructure\CQRS\Command\Bus\RunAnOperation\RunAnOperation;
use App\Tests\Infrastructure\CQRS\Command\Bus\RunAnOperation\RunAnOperationCommandHandler;
use App\Tests\Infrastructure\CQRS\Command\Bus\RunAnOperationCommand\RunAnOperationCommandCommandHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InMemoryCommandBusTest extends KernelTestCase
{
    public function testDispatch(): void
    {
        $commandBus = new InMemoryCommandBus([
            new RunAnOperationCommandHandler(),
        ]);

        $this->assertEquals([
            RunAnOperation::class,
        ],
            $commandBus->getAvailableCommands());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This is a test command and it is called');

        $commandBus->dispatch(new RunAnOperation('test'));
    }

    public function testDispatchWhenNotRegistered(): void
    {
        $commandBus = new InMemoryCommandBus([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The command has not a valid handler: App\Tests\Infrastructure\CQRS\Command\Bus\RunAnOperation\RunAnOperation');

        $commandBus->dispatch(new RunAnOperation('test'));
    }

    public function testDispatchWithoutCorrespondingCommand(): void
    {
        $this->expectException(CanNotRegisterCommandHandler::class);
        $this->expectExceptionMessage('No corresponding command for commandHandler "App\Tests\Infrastructure\CQRS\Command\Bus\RunOperationWithoutACommandCommandHandler" found');

        new InMemoryCommandBus([
            new RunOperationWithoutACommandCommandHandler(),
        ]);
    }

    public function testDispatchWithInvalidCommandName(): void
    {
        $this->expectException(CanNotRegisterCommandHandler::class);
        $this->expectExceptionMessage('Command name cannot end with "command"');

        new InMemoryCommandBus([
            new RunAnOperationCommandCommandHandler(),
        ]);
    }

    public function testDispatchWithInvalidCommandHandlerName(): void
    {
        $this->expectException(CanNotRegisterCommandHandler::class);
        $this->expectExceptionMessage('Fqcn "App\Tests\Infrastructure\CQRS\Command\Bus\RunOperationWithInvalidNameHandler" does not end with "CommandHandler"');

        new InMemoryCommandBus([
            new RunOperationWithInvalidNameHandler(),
        ]);
    }
}
