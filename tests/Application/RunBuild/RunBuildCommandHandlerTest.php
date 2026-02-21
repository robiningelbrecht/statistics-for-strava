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

    private function mockSuccessfulProcesses(string $stepTiming = ''): void
    {
        $this->processFactory
            ->method('create')
            ->willReturnCallback(function (array $command) use ($stepTiming): Process {
                $steps = $this->extractStepValues($command);
                $lines = array_map(
                    fn (string $step): string => '' !== $stepTiming
                        ? sprintf('  ✓ %s (%s)', BuildStep::from($step)->getLabel(), $stepTiming)
                        : sprintf('  ✓ %s', BuildStep::from($step)->getLabel()),
                    $steps,
                );

                return $this->createProcess(output: implode("\n", $lines));
            });
    }

    private function mockProcessesWithFailure(BuildStep $failingStep, string $errorOutput): void
    {
        $this->processFactory
            ->method('create')
            ->willReturnCallback(function (array $command) use ($failingStep, $errorOutput): Process {
                $steps = $this->extractStepValues($command);

                if (!in_array($failingStep->value, $steps, true)) {
                    $lines = array_map(
                        fn (string $step): string => sprintf('  ✓ %s', BuildStep::from($step)->getLabel()),
                        $steps,
                    );

                    return $this->createProcess(output: implode("\n", $lines));
                }

                $lines = [];
                foreach ($steps as $stepValue) {
                    if ($stepValue === $failingStep->value) {
                        $lines[] = sprintf('  × %s', BuildStep::from($stepValue)->getLabel());
                        break;
                    }
                    $lines[] = sprintf('  ✓ %s', BuildStep::from($stepValue)->getLabel());
                }

                return $this->createProcess(
                    successful: false,
                    output: implode("\n", $lines),
                    errorOutput: $errorOutput,
                );
            });
    }

    /**
     * @param string[] $command
     *
     * @return string[]
     */
    private function extractStepValues(array $command): array
    {
        return array_values(array_filter(
            $command,
            fn (string $arg): bool => null !== BuildStep::tryFrom($arg),
        ));
    }

    private function createProcess(
        bool $successful = true,
        string $output = '',
        string $errorOutput = '',
    ): Process {
        $process = $this->createStub(Process::class);
        $process->method('start')->willReturnCallback(function (?callable $callback = null) use ($output): void {
            if (null !== $callback && '' !== $output) {
                $callback(Process::OUT, $output);
            }
        });
        $process->method('isRunning')->willReturn(false);
        $process->method('isSuccessful')->willReturn($successful);
        $process->method('getOutput')->willReturn($output);
        $process->method('getErrorOutput')->willReturn($errorOutput);

        return $process;
    }
}
