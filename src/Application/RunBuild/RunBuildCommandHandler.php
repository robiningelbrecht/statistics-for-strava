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
use App\Domain\Activity\ActivityRepository;
use App\Infrastructure\Console\ProgressBar;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Time\Clock\Clock;

final readonly class RunBuildCommandHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commandBus,
        private ActivityRepository $activityRepository,
        private GearImportStatus $gearImportStatus,
        private MigrationRunner $migrationRunner,
        private Clock $clock,
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
        if ($this->activityRepository->count() <= 0) {
            $output->writeln('<error>Wait until at least one Strava activity has been imported before building the app</error>');

            return;
        }

        if (!$this->gearImportStatus->isComplete()) {
            $output->block('[WARNING] Some of your gear hasn’t been imported yet. This is most likely due to Strava API rate limits being reached. As a result, your gear statistics may currently be incomplete.

This is not a bug, once all your activities have been imported, your gear statistics will update automatically and be complete.', null, 'fg=black;bg=yellow', ' ', true);
        }

        $now = $this->clock->getCurrentDateTimeImmutable();

        $output->writeln('Building app...');
        $output->newLine();

        $commandsWithMessages = [
            'Configuring locale' => new ConfigureAppLocale(),
            'Configuring theme colors' => new ConfigureAppColors(),
            'Building Manifest' => new BuildManifest(),
            'Building index' => new BuildIndexHtml($now),
            'Building dashboard' => new BuildDashboardHtml(),
            'Building activities' => new BuildActivitiesHtml($now),
            'Building gpx files' => new BuildGpxFiles(),
            'Building monthly stats' => new BuildMonthlyStatsHtml($now),
            'Building gear stats' => new BuildGearStatsHtml($now),
            'Building gear maintenance' => new BuildGearMaintenanceHtml(),
            'Building eddington' => new BuildEddingtonHtml($now),
            'Building segments' => new BuildSegmentsHtml($now),
            'Building heatmap' => new BuildHeatmapHtml($now),
            'Building best efforts' => new BuildBestEffortsHtml(),
            'Building rewind' => new BuildRewindHtml($now),
            'Building challenges' => new BuildChallengesHtml($now),
            'Building photos' => new BuildPhotosHtml(),
            'Building badges' => new BuildBadgeSvg($now),
        ];

        $progressBar = new ProgressBar($output, count($commandsWithMessages));
        $progressBar->start();

        foreach ($commandsWithMessages as $message => $command) {
            $progressBar->updateMessage($message);
            $progressBar->advance();
            $this->commandBus->dispatch($command);
        }

        $progressBar->finish();
        $output->writeln('');
    }
}
