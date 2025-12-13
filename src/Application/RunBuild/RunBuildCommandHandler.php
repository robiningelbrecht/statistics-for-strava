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

        $output->writeln('Configuring locale...');
        $this->commandBus->dispatch(new ConfigureAppLocale());
        $output->writeln('Configuring theme colors...');
        $this->commandBus->dispatch(new ConfigureAppColors());
        $output->writeln('Building Manifest...');
        $this->commandBus->dispatch(new BuildManifest());
        $output->writeln('Building App...');
        $output->writeln('  => Building index');
        $this->commandBus->dispatch(new BuildIndexHtml($now));
        $output->writeln('  => Building dashboard');
        $this->commandBus->dispatch(new BuildDashboardHtml());
        $output->writeln('  => Building activities');
        $this->commandBus->dispatch(new BuildActivitiesHtml($now));
        $output->writeln('  => Building gpx files');
        $this->commandBus->dispatch(new BuildGpxFiles());
        $output->writeln('  => Building monthly-stats');
        $this->commandBus->dispatch(new BuildMonthlyStatsHtml($now));
        $output->writeln('  => Building gear-stats');
        $this->commandBus->dispatch(new BuildGearStatsHtml($now));
        $output->writeln('  => Building gear-maintenance');
        $this->commandBus->dispatch(new BuildGearMaintenanceHtml());
        $output->writeln('  => Building eddington');
        $this->commandBus->dispatch(new BuildEddingtonHtml($now));
        $output->writeln('  => Building segments');
        $this->commandBus->dispatch(new BuildSegmentsHtml($now));
        $output->writeln('  => Building heatmap');
        $this->commandBus->dispatch(new BuildHeatmapHtml($now));
        $output->writeln('  => Building best-efforts');
        $this->commandBus->dispatch(new BuildBestEffortsHtml());
        $output->writeln('  => Building rewind');
        $this->commandBus->dispatch(new BuildRewindHtml($now));
        $output->writeln('  => Building challenges');
        $this->commandBus->dispatch(new BuildChallengesHtml($now));
        $output->writeln('  => Building photos');
        $this->commandBus->dispatch(new BuildPhotosHtml());
        $output->writeln('  => Building badges');
        $this->commandBus->dispatch(new BuildBadgeSvg($now));
    }
}
