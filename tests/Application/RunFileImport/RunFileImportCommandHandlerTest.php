<?php

declare(strict_types=1);

namespace App\Tests\Application\RunFileImport;

use App\Application\Import\CalculateActivityMetrics\CalculateActivityMetrics;
use App\Application\Import\FileImport\ImportActivityFiles;
use App\Application\RunFileImport\RunFileImport;
use App\Application\RunFileImport\RunFileImportCommandHandler;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\Infrastructure\FileSystem\SuccessfulPermissionChecker;
use App\Tests\Infrastructure\FileSystem\UnwritablePermissionChecker;
use App\Tests\SpyOutput;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Style\SymfonyStyle;

class RunFileImportCommandHandlerTest extends ContainerTestCase
{
    private RunFileImportCommandHandler $handler;
    private SpyCommandBus $commandBus;
    private MockObject $connection;

    public function testHandle(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('VACUUM');

        $this->handler->handle(new RunFileImport(
            output: new SymfonyStyle(new StringInput('input'), new SpyOutput()),
        ));

        $dispatched = array_map(static fn (object $command): string => $command::class, $this->commandBus->getDispatchedCommands());
        $this->assertSame([
            ImportActivityFiles::class,
            CalculateActivityMetrics::class,
        ], $dispatched);
    }

    public function testHandleWithInsufficientPermissions(): void
    {
        $this->connection
            ->expects($this->never())
            ->method('executeStatement');

        $this->handler = new RunFileImportCommandHandler(
            $this->commandBus = new SpyCommandBus(),
            new UnwritablePermissionChecker(),
            $this->connection,
        );

        $this->handler->handle(new RunFileImport(
            output: new SymfonyStyle(new StringInput('input'), new SpyOutput()),
        ));

        $this->assertEmpty($this->commandBus->getDispatchedCommands());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new RunFileImportCommandHandler(
            $this->commandBus = new SpyCommandBus(),
            new SuccessfulPermissionChecker(),
            $this->connection = $this->createMock(Connection::class),
        );
    }
}
