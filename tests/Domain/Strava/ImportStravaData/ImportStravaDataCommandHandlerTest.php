<?php

namespace App\Tests\Domain\Strava\ImportStravaData;

use App\Domain\Strava\ImportStravaData\ImportStravaData;
use App\Domain\Strava\ImportStravaData\ImportStravaDataCommandHandler;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\Infrastructure\FileSystem\SuccessfulPermissionChecker;
use App\Tests\Infrastructure\FileSystem\UnwritablePermissionChecker;
use App\Tests\SpyOutput;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportStravaDataCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private ImportStravaDataCommandHandler $importStravaDataCommandHandler;
    private CommandBus $commandBus;
    private MockObject $migrationRunner;
    private MockObject $connection;

    public function testHandle(): void
    {
        $this->migrationRunner
            ->expects($this->once())
            ->method('run');

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('VACUUM');

        $output = new SpyOutput();
        $this->importStravaDataCommandHandler->handle(new ImportStravaData(
            output: new SymfonyStyle(new StringInput('input'), $output),
        ));
        $this->assertMatchesTextSnapshot(str_replace(' ', '', $output));
        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
    }

    public function testHandleWithInsufficientPermissions(): void
    {
        $this->importStravaDataCommandHandler = new ImportStravaDataCommandHandler(
            $this->getContainer()->get(Strava::class),
            $this->commandBus = new SpyCommandBus(),
            $this->migrationRunner = $this->createMock(MigrationRunner::class),
            new UnwritablePermissionChecker(),
            $this->connection = $this->createMock(Connection::class),
        );

        $this->migrationRunner
            ->expects($this->never())
            ->method('run');

        $this->connection
            ->expects($this->never())
            ->method('executeStatement')
            ->with('VACUUM');

        $output = new SpyOutput();
        $this->importStravaDataCommandHandler->handle(new ImportStravaData(
            output: new SymfonyStyle(new StringInput('input'), $output),
        ));
        $this->assertMatchesTextSnapshot(str_replace(' ', '', $output));
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->importStravaDataCommandHandler = new ImportStravaDataCommandHandler(
            $this->getContainer()->get(Strava::class),
            $this->commandBus = new SpyCommandBus(),
            $this->migrationRunner = $this->createMock(MigrationRunner::class),
            new SuccessfulPermissionChecker(),
            $this->connection = $this->createMock(Connection::class),
        );
    }
}
