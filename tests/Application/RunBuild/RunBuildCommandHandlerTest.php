<?php

namespace App\Tests\Application\RunBuild;

use App\Application\Import\ImportGear\GearImportStatus;
use App\Application\RunBuild\BuildStep;
use App\Application\RunBuild\RunBuild;
use App\Application\RunBuild\RunBuildCommandHandler;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Gear\GearId;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Infrastructure\Daemon\ProcessFactory;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Gear\ImportedGear\ImportedGearBuilder;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\SpyOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class RunBuildCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private RunBuildCommandHandler $buildAppCommandHandler;
    private SpyCommandBus $commandBus;
    private MockObject $migrationRunner;
    private ProcessFactory $processFactory;

    public function testHandle(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
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

        $this->mockSuccessfulProcesses();

        $output = new SpyOutput();
        $this->buildAppCommandHandler->handle(new RunBuild(
            output: new SymfonyStyle(new StringInput('input'), $output),
        ));
        $this->assertMatchesTextSnapshot(str_replace(' ', '', $output));
        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
    }

    public function testHandleDisplaysProcessOutput(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
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

        $this->mockSuccessfulProcesses('0.5s, 10MB');

        $output = new SpyOutput();
        $this->buildAppCommandHandler->handle(new RunBuild(
            output: new SymfonyStyle(new StringInput('input'), $output),
        ));

        $outputString = (string) $output;
        $this->assertStringContainsString('0.5s, 10MB', $outputString);
        foreach (BuildStep::cases() as $step) {
            $this->assertStringContainsString($step->getLabel(), $outputString);
        }
    }

    public function testHandleWhenNotAllGearHasBeenImportedYet(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
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

        $this->mockSuccessfulProcesses();

        $output = new SpyOutput();
        $this->buildAppCommandHandler->handle(new RunBuild(
            output: new SymfonyStyle(new StringInput('input'), $output),
        ));
        $this->assertStringContainsString('[WARNING] Some of your gear has not been imported yet', $output);
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

    public function testHandleWhenBuildStepFails(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
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

        $failingStep = BuildStep::INDEX;
        $this->mockProcessesWithFailure($failingStep, 'Something went wrong');

        $output = new SpyOutput();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Build step\(s\).*failed/');

        $this->buildAppCommandHandler->handle(new RunBuild(
            output: new SymfonyStyle(new StringInput('input'), $output),
        ));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processFactory = $this->createStub(ProcessFactory::class);

        $this->buildAppCommandHandler = new RunBuildCommandHandler(
            commandBus: $this->commandBus = new SpyCommandBus(),
            activityIdRepository: $this->getContainer()->get(ActivityIdRepository::class),
            gearImportStatus: $this->getContainer()->get(GearImportStatus::class),
            migrationRunner: $this->migrationRunner = $this->createMock(MigrationRunner::class),
            processFactory: $this->processFactory,
        );
    }

    private function mockSuccessfulProcesses(string $processOutput = ''): void
    {
        $this->processFactory
            ->method('create')
            ->willReturnCallback(function () use ($processOutput): Process {
                return $this->createSuccessfulProcess($processOutput);
            });
    }

    private function mockProcessesWithFailure(BuildStep $failingStep, string $errorOutput): void
    {
        $this->processFactory
            ->method('create')
            ->willReturnCallback(function (array $command) use ($failingStep, $errorOutput): Process {
                if (($command[2] ?? null) === $failingStep->value) {
                    return $this->createFailedProcess($errorOutput);
                }

                return $this->createSuccessfulProcess();
            });
    }

    private function createSuccessfulProcess(string $output = ''): Process
    {
        $process = $this->createStub(Process::class);
        $process->method('isRunning')->willReturn(false);
        $process->method('isSuccessful')->willReturn(true);
        $process->method('getOutput')->willReturn($output);

        return $process;
    }

    private function createFailedProcess(string $errorOutput): Process
    {
        $process = $this->createStub(Process::class);
        $process->method('isRunning')->willReturn(false);
        $process->method('isSuccessful')->willReturn(false);
        $process->method('getErrorOutput')->willReturn($errorOutput);
        $process->method('getOutput')->willReturn('');

        return $process;
    }
}
