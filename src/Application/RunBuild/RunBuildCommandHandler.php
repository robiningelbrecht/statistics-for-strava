<?php

declare(strict_types=1);

namespace App\Application\RunBuild;

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
use App\Application\Build\ConfigureAppColors\ConfigureAppColors;
use App\Application\Build\ConfigureAppLocale\ConfigureAppLocale;
use App\Application\Import\ImportGear\GearImportStatus;
use App\Domain\Activity\ActivityIdRepository;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Daemon\ProcessForker;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\Time\ResourceUsage\Timer;

final readonly class RunBuildCommandHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commandBus,
        private ActivityIdRepository $activityIdRepository,
        private GearImportStatus $gearImportStatus,
        private MigrationRunner $migrationRunner,
        private Clock $clock,
        private ProcessForker $processForker,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof RunBuild);

        $output = $command->getOutput();
        if (!$this->migrationRunner->isAtLatestVersion()) {
            $output->writeln('<error>Your database is not up to date with the migration schema. Run the import command before building the HTML files</error>');

            return;
        }
        if ($this->activityIdRepository->count() <= 0) {
            $output->writeln('<error>Wait until at least one Strava activity has been imported before building the app</error>');

            return;
        }

        if (!$this->gearImportStatus->isComplete()) {
            $output->block('[WARNING] Some of your gear has not been imported yet. This is most likely due to Strava API rate limits being reached. As a result, your gear statistics may currently be incomplete.

This is not a bug, once all your activities have been imported, your gear statistics will update automatically and be complete.', null, 'fg=black;bg=yellow', ' ', true);
        }

        $now = $this->clock->getCurrentDateTimeImmutable();

        $output->writeln('Building app...');
        $output->newLine();

        $this->commandBus->dispatch(new ConfigureAppLocale());
        $this->commandBus->dispatch(new ConfigureAppColors());

        $commandGroups = [
            [
                'Built manifest' => new BuildManifest(),
                'Built index' => new BuildIndexHtml($now),
                'Built activities' => new BuildActivitiesHtml($now),
            ],
            [
                'Built segments' => new BuildSegmentsHtml(),
                'Built best efforts' => new BuildBestEffortsHtml(),
                'Built rewind' => new BuildRewindHtml($now),
                'Built challenges' => new BuildChallengesHtml($now),
            ],
            [
                'Built dashboard' => new BuildDashboardHtml(),
                'Built gpx files' => new BuildGpxFiles(),
                'Built monthly stats' => new BuildMonthlyStatsHtml($now),
                'Built gear stats' => new BuildGearStatsHtml($now),
                'Built gear maintenance' => new BuildGearMaintenanceHtml(),
                'Built eddington' => new BuildEddingtonHtml($now),
                'Built heatmap' => new BuildHeatmapHtml($now),
                'Built photos' => new BuildPhotosHtml(),
                'Built badges' => new BuildBadgeSvg($now),
            ],
        ];

        $maxMessageLength = max(array_map(
            mb_strlen(...),
            array_keys(array_merge(...$commandGroups)),
        ));

        $pids = [];
        $printedWarning = false;
        foreach ($commandGroups as $group) {
            $pid = $this->processForker->fork();

            if ($pid > 0) {
                $output->writeln(sprintf('<fg=gray>Started new parallel process with pid %s</>', $pid));
                // Store child process id to be able to use pcntl_waitpid().
                $pids[] = $pid;
                continue;
            }

            foreach ($group as $message => $buildCommand) {
                $timer = new Timer();
                $timer->start();
                $this->commandBus->dispatch($buildCommand);
                $timer->stop();

                $output->writeln(
                    sprintf(
                        '  <info>✓</info> %s  <fg=gray>(time: %ss)</>',
                        str_pad($message, $maxMessageLength),
                        number_format($timer->getRunTimeInSeconds(), 3, '.', '')
                    ));
            }

            if (-1 === $pid) {
                if (!$printedWarning) {
                    // Unable to fork new process.
                    $output->writeln('<comment>Unable to start parallel execution. Falling back to sequential execution.</comment>');
                    $printedWarning = true;
                }
            } elseif (0 === $pid) {
                // Child process needs to be exited.
                exit(0);
            }
        }

        if ([] !== $pids) {
            $output->newLine();
        }

        foreach ($pids as $pid) {
            $status = 0;
            $this->processForker->waitPid($pid, $status);
        }
        $output->newLine();
    }
}
