<?php

namespace App\Tests\Application\RunBuild;

use App\Application\Import\ImportGear\GearImportStatus;
use App\Application\RunBuild\RunBuild;
use App\Application\RunBuild\RunBuildCommandHandler;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Gear\GearId;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Gear\ImportedGear\ImportedGearBuilder;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\SpyOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Style\SymfonyStyle;

class RunBuildCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private RunBuildCommandHandler $buildAppCommandHandler;
    private CommandBus $commandBus;
    private MockObject $migrationRunner;
    private MockObject $logger;

    public function testHandle(): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->withGearId(GearId::fromUnprefixed(4))
                ->build(), []
        ));
        $this->getContainer()->get(ImportedGearRepository::class)->save(
            ImportedGearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed(4))
                ->build()
        );

        $this->migrationRunner
            ->expects($this->once())
            ->method('isAtLatestVersion')
            ->willReturn(true);

        $output = new SpyOutput();
        $this->buildAppCommandHandler->handle(new RunBuild(
            output: new SymfonyStyle(new StringInput('input'), $output),
        ));
        $this->assertMatchesTextSnapshot(str_replace(' ', '', $output));
        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
    }

    public function testHandleWhenNotAllGearHasBeenImportedYet(): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->withGearId(GearId::fromUnprefixed(4))
                ->build(),
            [
                'gear_id' => '4',
            ]
        ));

        $this->migrationRunner
            ->expects($this->once())
            ->method('isAtLatestVersion')
            ->willReturn(true);

        $output = new SpyOutput();
        $this->buildAppCommandHandler->handle(new RunBuild(
            output: new SymfonyStyle(new StringInput('input'), $output),
        ));
        $this->assertStringContainsString('[WARNING] Some of your gear hasn’t been imported yet', $output);
    }

    public function testHandleWhenStravaImportIsNotCompleted(): void
    {
        $this->migrationRunner
            ->expects($this->once())
            ->method('isAtLatestVersion')
            ->willReturn(true);

        $output = new SpyOutput();
        $this->buildAppCommandHandler->handle(new RunBuild(
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
        $this->buildAppCommandHandler->handle(new RunBuild(
            output: new SymfonyStyle(new StringInput('input'), $output),
        ));

        $this->assertMatchesTextSnapshot($output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->buildAppCommandHandler = new RunBuildCommandHandler(
            commandBus: $this->commandBus = new SpyCommandBus(),
            activityIdRepository: $this->getContainer()->get(ActivityIdRepository::class),
            gearImportStatus: $this->getContainer()->get(GearImportStatus::class),
            migrationRunner: $this->migrationRunner = $this->createMock(MigrationRunner::class),
            clock: PausedClock::on(SerializableDateTime::fromString('2023-10-17 16:15:04')),
        );
    }
}
