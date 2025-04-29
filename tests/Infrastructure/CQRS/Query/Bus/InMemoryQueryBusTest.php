<?php

namespace App\Tests\Infrastructure\CQRS\Query\Bus;

use App\Infrastructure\CQRS\CanNotRegisterCQRSHandler;
use App\Infrastructure\CQRS\Query\Bus\InMemoryQueryBus;
use App\Tests\Infrastructure\CQRS\Query\Bus\FindSomething\FindSomething;
use App\Tests\Infrastructure\CQRS\Query\Bus\FindSomething\FindSomethingQueryHandler;
use App\Tests\Infrastructure\CQRS\Query\Bus\FindSomething\FindSomethingResponse;
use App\Tests\Infrastructure\CQRS\Query\Bus\FindSomethingQuery\FindSomethingQueryQueryHandler;
use App\Tests\Infrastructure\CQRS\Query\Bus\FindSomethingThrowHandlerFailedException\FindSomethingThrowHandlerFailedException;
use App\Tests\Infrastructure\CQRS\Query\Bus\FindSomethingThrowHandlerFailedException\FindSomethingThrowHandlerFailedExceptionQueryHandler;
use App\Tests\Infrastructure\CQRS\Query\Bus\FindSomethingWithoutQuery\FindSomethingWithoutQueryQueryHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class InMemoryQueryBusTest extends TestCase
{
    public function testAsk(): void
    {
        $queryBus = new InMemoryQueryBus([
            new FindSomethingQueryHandler(),
        ]);

        $this->assertEquals(
            new FindSomethingResponse(),
            $queryBus->ask(new FindSomething()),
        );
    }

    public function testAskWhenHandlerFailedExceptionIsThrown(): void
    {
        $commandBus = new InMemoryQueryBus([
            new FindSomethingThrowHandlerFailedExceptionQueryHandler(),
        ]);

        $this->expectException(HandlerFailedException::class);

        $commandBus->ask(new FindSomethingThrowHandlerFailedException());
    }

    public function testAskWhenNotRegistered(): void
    {
        $commandBus = new InMemoryQueryBus([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The query has not a valid handler: App\Tests\Infrastructure\CQRS\Query\Bus\FindSomething\FindSomething');

        $commandBus->ask(new FindSomething());
    }

    public function testAskWithoutCorrespondingCommand(): void
    {
        $this->expectException(CanNotRegisterCQRSHandler::class);
        $this->expectExceptionMessage('No corresponding object for QueryHandler "App\Tests\Infrastructure\CQRS\Query\Bus\FindSomethingWithoutQuery\FindSomethingWithoutQueryQueryHandler" found. Expected namespace: App\Tests\Infrastructure\CQRS\Query\Bus\FindSomethingWithoutQuery\FindSomethingWithoutQuery');

        new InMemoryQueryBus([
            new FindSomethingWithoutQueryQueryHandler(),
        ]);
    }

    public function testAskWithInvalidCommandName(): void
    {
        $this->expectException(CanNotRegisterCQRSHandler::class);
        $this->expectExceptionMessage('Object name cannot end with "Query"');

        new InMemoryQueryBus([
            new FindSomethingQueryQueryHandler(),
        ]);
    }

    public function testAskWithInvalidCommandHandlerName(): void
    {
        $this->expectException(CanNotRegisterCQRSHandler::class);
        $this->expectExceptionMessage('Fqcn "App\Tests\Infrastructure\CQRS\Query\Bus\FindSomethingWithInvalidNameQuery" does not end with "QueryHandler"');

        new InMemoryQueryBus([
            new FindSomethingWithInvalidNameQuery(),
        ]);
    }
}
