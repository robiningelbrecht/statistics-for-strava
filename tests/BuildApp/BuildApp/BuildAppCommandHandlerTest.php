<?php

namespace App\Tests\BuildApp\BuildApp;

use App\BuildApp\BuildApp\BuildApp;
use App\BuildApp\BuildApp\BuildAppCommandHandler;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\StravaDataImportStatus;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\SpyOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildAppCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private BuildAppCommandHandler $buildAppCommandHandler;
    private CommandBus $commandBus;
    private MockObject $migrationRunner;
    private MockObject $logger;

    public function testHandle(): void
    {
        $this->getContainer()->get(KeyValueStore::class)->save(KeyValue::fromState(
            Key::STRAVA_GEAR_IMPORT,
            Value::fromString('yes')
        ));

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->build(), []
        ));

        $this->migrationRunner
            ->expects($this->once())
            ->method('isAtLatestVersion')
            ->willReturn(true);

        $output = new SpyOutput();
        $this->buildAppCommandHandler->handle(new BuildApp(
            consoleApplication: new Application('mock', 'v1.0.0'),
            output: new SymfonyStyle(new StringInput('input'), $output),
        ));
        $this->assertMatchesTextSnapshot(str_replace(' ', '', $output));
        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
    }

    public function testHandleWhenStravaImportIsNotCompleted(): void
    {
        $this->migrationRunner
            ->expects($this->once())
            ->method('isAtLatestVersion')
            ->willReturn(true);

        $output = new SpyOutput();
        $this->buildAppCommandHandler->handle(new BuildApp(
            consoleApplication: new Application('mock', 'v1.0.0'),
            output: new SymfonyStyle(new StringInput('input'), $output),
        ));

        $this->assertMatchesTextSnapshot($output);
    }

    public function testHandleWhenMigrationSchemaNotUpToDate(): void
    {
        $this->migrationRunner
            ->expects($this->once())
            ->method('isAtLatestVersion')
            ->willReturn(false);

        $output = new SpyOutput();
        $this->buildAppCommandHandler->handle(new BuildApp(
            consoleApplication: new Application('mock', 'v1.0.0'),
            output: new SymfonyStyle(new StringInput('input'), $output),
        ));

        $this->assertMatchesTextSnapshot($output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->buildAppCommandHandler = new BuildAppCommandHandler(
            commandBus: $this->commandBus = new SpyCommandBus(),
            stravaDataImportStatus: $this->getContainer()->get(StravaDataImportStatus::class),
            migrationRunner: $this->migrationRunner = $this->createMock(MigrationRunner::class),
            clock: PausedClock::on(SerializableDateTime::fromString('2023-10-17 16:15:04')),
        );
    }
}
