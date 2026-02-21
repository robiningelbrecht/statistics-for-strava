<?php

namespace App\Tests\Console\Build;

use App\Application\Build\BuildActivitiesHtml\BuildActivitiesHtml;
use App\Application\Build\BuildBadgeSvg\BuildBadgeSvg;
use App\Application\Build\BuildBestEffortsHtml\BuildBestEffortsHtml;
use App\Application\Build\BuildChallengesHtml\BuildChallengesHtml;
use App\Application\Build\BuildDashboardHtml\BuildDashboardHtml;
use App\Application\Build\BuildEddingtonHtml\BuildEddingtonHtml;
use App\Application\Build\BuildGearMaintenanceHtml\BuildGearMaintenanceHtml;
use App\Application\Build\BuildGearStatsHtml\BuildGearStatsHtml;
use App\Application\Build\BuildGpxFiles\BuildGpxFiles;
use App\Application\Build\BuildHeatmapHtml\BuildHeatmapHtml;
use App\Application\Build\BuildIndexHtml\BuildIndexHtml;
use App\Application\Build\BuildManifest\BuildManifest;
use App\Application\Build\BuildMonthlyStatsHtml\BuildMonthlyStatsHtml;
use App\Application\Build\BuildPhotosHtml\BuildPhotosHtml;
use App\Application\Build\BuildRewindHtml\BuildRewindHtml;
use App\Application\Build\BuildSegmentsHtml\BuildSegmentsHtml;
use App\Application\Build\ConfigureAppLocale\ConfigureAppLocale;
use App\Application\RunBuild\BuildStep;
use App\Console\Build\BuildStepConsoleCommand;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class BuildStepConsoleCommandTest extends ConsoleCommandTestCase
{
    private BuildStepConsoleCommand $buildStepConsoleCommand;
    private MockObject $commandBus;

    #[DataProvider(methodName: 'provideBuildSteps')]
    public function testExecute(BuildStep $step, string $expectedCommandClass): void
    {
        $dispatchedCommands = [];
        $this->commandBus
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (DomainCommand $command) use (&$dispatchedCommands): void {
                $dispatchedCommands[] = $command;
            });

        $command = $this->getCommandInApplication('app:strava:build-step');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'step' => $step->value,
        ]);

        $this->assertInstanceOf(ConfigureAppLocale::class, $dispatchedCommands[0]);
        $this->assertInstanceOf($expectedCommandClass, $dispatchedCommands[1]);
    }

    public function testExecuteWithInvalidStep(): void
    {
        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $command = $this->getCommandInApplication('app:strava:build-step');
        $commandTester = new CommandTester($command);

        $this->expectException(\ValueError::class);

        $commandTester->execute([
            'command' => $command->getName(),
            'step' => 'invalid-step',
        ]);
    }

    public static function provideBuildSteps(): array
    {
        return [
            [BuildStep::INDEX, BuildIndexHtml::class],
            [BuildStep::ACTIVITIES, BuildActivitiesHtml::class],
            [BuildStep::SEGMENTS, BuildSegmentsHtml::class],
            [BuildStep::DASHBOARD, BuildDashboardHtml::class],
            [BuildStep::HEATMAP, BuildHeatmapHtml::class],
            [BuildStep::MONTHLY_STATS, BuildMonthlyStatsHtml::class],
            [BuildStep::GPX_FILES, BuildGpxFiles::class],
            [BuildStep::GEAR_STATS, BuildGearStatsHtml::class],
            [BuildStep::REWIND, BuildRewindHtml::class],
            [BuildStep::CHALLENGES, BuildChallengesHtml::class],
            [BuildStep::BEST_EFFORTS, BuildBestEffortsHtml::class],
            [BuildStep::EDDINGTON, BuildEddingtonHtml::class],
            [BuildStep::GEAR_MAINTENANCE, BuildGearMaintenanceHtml::class],
            [BuildStep::MANIFEST, BuildManifest::class],
            [BuildStep::PHOTOS, BuildPhotosHtml::class],
            [BuildStep::BADGES, BuildBadgeSvg::class],
        ];
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->buildStepConsoleCommand = new BuildStepConsoleCommand(
            $this->commandBus = $this->createMock(CommandBus::class),
            PausedClock::fromString('2025-12-04'),
            new FixedResourceUsage(),
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->buildStepConsoleCommand;
    }
}
