<?php

declare(strict_types=1);

namespace App\BuildApp\BuildApp;

use App\BuildApp\BuildActivitiesHtml\BuildActivitiesHtml;
use App\BuildApp\BuildBadgeSvg\BuildBadgeSvg;
use App\BuildApp\BuildBestEffortsHtml\BuildBestEffortsHtml;
use App\BuildApp\BuildChallengesHtml\BuildChallengesHtml;
use App\BuildApp\BuildDashboardHtml\BuildDashboardHtml;
use App\BuildApp\BuildEddingtonHtml\BuildEddingtonHtml;
use App\BuildApp\BuildGearMaintenanceHtml\BuildGearMaintenanceHtml;
use App\BuildApp\BuildGearStatsHtml\BuildGearStatsHtml;
use App\BuildApp\BuildGpxFiles\BuildGpxFiles;
use App\BuildApp\BuildHeatmapHtml\BuildHeatmapHtml;
use App\BuildApp\BuildIndexHtml\BuildIndexHtml;
use App\BuildApp\BuildManifest\BuildManifest;
use App\BuildApp\BuildMonthlyStatsHtml\BuildMonthlyStatsHtml;
use App\BuildApp\BuildPhotosHtml\BuildPhotosHtml;
use App\BuildApp\BuildRewindHtml\BuildRewindHtml;
use App\BuildApp\BuildSegmentsHtml\BuildSegmentsHtml;
use App\BuildApp\ConfigureAppColors\ConfigureAppColors;
use App\BuildApp\ConfigureAppLocale\ConfigureAppLocale;
use App\Domain\Strava\StravaDataImportStatus;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Time\Clock\Clock;

final readonly class BuildAppCommandHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commandBus,
        private StravaDataImportStatus $stravaDataImportStatus,
        private MigrationRunner $migrationRunner,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildApp);

        $consoleApplication = $command->getConsoleApplication();
        $output = $command->getOutput();
        if (!$this->migrationRunner->isAtLatestVersion($consoleApplication)) {
            $output->writeln('<error>Your database is not up to date with the migration schema. Run the import command before building the HTML files</error>');

            return;
        }
        if (!$this->stravaDataImportStatus->isCompleted()) {
            $output->writeln('<error>Wait until all Strava data has been imported before building the app</error>');

            return;
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
