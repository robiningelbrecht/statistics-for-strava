<?php

namespace App\Console;

use App\BuildApp\AppVersion;
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
use App\Domain\Integration\Notification\SendNotification\SendNotification;
use App\Domain\Strava\StravaDataImportStatus;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Logging\LoggableConsoleOutput;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[WithMonologChannel('console-output')]
#[AsCommand(name: 'app:strava:build-files', description: 'Build Strava files')]
final class BuildAppConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly StravaDataImportStatus $stravaDataImportStatus,
        private readonly ResourceUsage $resourceUsage,
        private readonly MigrationRunner $migrationRunner,
        private readonly Clock $clock,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, new LoggableConsoleOutput($output, $this->logger));
        if (is_null($consoleApplication = $this->getApplication())) {
            throw new \RuntimeException('Console application is not set.');
        }

        if (!$this->migrationRunner->isAtLatestVersion($consoleApplication)) {
            $output->writeln('<error>Your database is not up to date with the migration schema. Run the import command before building the HTML files</error>');

            return Command::FAILURE;
        }
        if (!$this->stravaDataImportStatus->isCompleted()) {
            $output->writeln('<error>Wait until all Strava data has been imported before building the app</error>');

            return Command::FAILURE;
        }
        $this->resourceUsage->startTimer();

        $now = $this->clock->getCurrentDateTimeImmutable();

        $output->block(
            messages: sprintf('Statistics for Strava %s', AppVersion::getSemanticVersion()),
            style: 'fg=black;bg=green',
            padding: true
        );

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

        $this->resourceUsage->stopTimer();
        $this->commandBus->dispatch(new SendNotification(
            title: 'Build successful',
            message: sprintf('New build of your Strava stats was successful in %ss', $this->resourceUsage->getRunTimeInSeconds()),
            tags: ['+1']
        ));

        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));

        return Command::SUCCESS;
    }
}
