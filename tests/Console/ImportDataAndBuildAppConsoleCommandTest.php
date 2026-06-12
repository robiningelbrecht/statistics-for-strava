<?php

namespace App\Tests\Console;

use App\Application\AppUrl;
use App\Console\Daemon\RunStravaImportAndBuildAppConsoleCommand;
use App\Console\ImportDataAndBuildAppConsoleCommand;
use App\Domain\Import\ImportMode;
use App\Domain\Strava\Strava;
use App\Infrastructure\Mutex\LockName;
use App\Infrastructure\Mutex\Mutex;
use App\Infrastructure\Serialization\Json;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\Infrastructure\Doctrine\Migrations\VoidMigrationRunner;
use App\Tests\Infrastructure\FileSystem\SuccessfulPermissionChecker;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Log\NullLogger;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ImportDataAndBuildAppConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private const string TODAY = '2025-12-04';


    private ImportDataAndBuildAppConsoleCommand $command;
    private SpyCommandBus $delegateCommandBus;

    public function testDelegatesImportToStravaImport(): void
    {
        $command = $this->getCommandInApplication('app:strava:import-data');
        $command->getApplication()->addCommand($this->buildStravaImportCommand());

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => 'app:strava:import-data']);

        $this->assertMatchesJsonSnapshot(Json::encode($this->delegateCommandBus->getDispatchedCommands()));
    }

    public function testDelegatesBuildToStravaImport(): void
    {
        $command = $this->getCommandInApplication('app:strava:build-files');
        $command->getApplication()->addCommand($this->buildStravaImportCommand());

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => 'app:strava:build-files']);

        $this->assertMatchesJsonSnapshot(Json::encode($this->delegateCommandBus->getDispatchedCommands()));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new ImportDataAndBuildAppConsoleCommand(
            new NullLogger(),
            ImportMode::STRAVA_API,
        );
    }

    private function buildStravaImportCommand(): RunStravaImportAndBuildAppConsoleCommand
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('executeStatement')->willReturn(0);

        return new RunStravaImportAndBuildAppConsoleCommand(
            commandBus: $this->delegateCommandBus = new SpyCommandBus(),
            resourceUsage: new FixedResourceUsage(),
            strava: $this->getContainer()->get(Strava::class),
            logger: new NullLogger(),
            mutex: new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString(self::TODAY),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            migrationRunner: new VoidMigrationRunner(),
            fileSystemPermissionChecker: new SuccessfulPermissionChecker(),
            connection: $connection,
            appUrl: AppUrl::fromString('http://localhost'),
            importMode: ImportMode::STRAVA_API,
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->command;
    }
}
