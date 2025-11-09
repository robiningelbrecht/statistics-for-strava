<?php

declare(strict_types=1);

namespace App\BuildApp;

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
use App\Domain\Activity\BestEffort\CalculateBestActivityEfforts\CalculateBestActivityEfforts;
use App\Domain\Activity\ImportActivities\ImportActivities;
use App\Domain\Activity\Lap\ImportActivityLaps\ImportActivityLaps;
use App\Domain\Activity\Split\ImportActivitySplits\ImportActivitySplits;
use App\Domain\Activity\Stream\CalculateBestStreamAverages\CalculateBestStreamAverages;
use App\Domain\Activity\Stream\CalculateNormalizedPower\CalculateNormalizedPower;
use App\Domain\Activity\Stream\CombinedStream\CalculateCombinedStreams\CalculateCombinedStreams;
use App\Domain\Activity\Stream\ImportActivityStreams\ImportActivityStreams;
use App\Domain\Athlete\ImportAthlete\ImportAthlete;
use App\Domain\Challenge\ImportChallenges\ImportChallenges;
use App\Domain\Gear\CustomGear\LinkCustomGearToActivities\LinkCustomGearToActivities;
use App\Domain\Gear\ImportGear\ImportGear;
use App\Domain\Integration\Notification\SendNotification\SendNotification;
use App\Domain\Segment\ImportSegments\ImportSegments;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaDataImportStatus;
use App\Infrastructure\Console\ConsoleApplicationAware;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Daemon\Cron\RunnableCronAction;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\FileSystem\PermissionChecker;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Doctrine\DBAL\Connection;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ImportAndBuildAppCronAction implements RunnableCronAction
{
    use ConsoleApplicationAware;

    public function __construct(
        private readonly Strava $strava,
        private readonly CommandBus $commandBus,
        private readonly StravaDataImportStatus $stravaDataImportStatus,
        private readonly ResourceUsage $resourceUsage,
        private readonly MigrationRunner $migrationRunner,
        private readonly PermissionChecker $fileSystemPermissionChecker,
        private readonly Connection $connection,
        private readonly AppUrl $appUrl,
        private readonly Clock $clock,
    ) {
    }

    public function getId(): string
    {
        return 'ImportAndBuildApp';
    }

    public function getMutexTtl(): int
    {
        return 1800;
    }

    public function run(SymfonyStyle $output): void
    {
        $output->block(
            messages: sprintf('Statistics for Strava %s', AppVersion::getSemanticVersion()),
            style: 'fg=black;bg=green',
            padding: true
        );

        $this->runImport($output);
        $this->runBuild($output);
    }

    public function runImport(SymfonyStyle $output): void
    {
        try {
            $this->fileSystemPermissionChecker->ensureWriteAccess();
        } catch (UnableToWriteFile|UnableToCreateDirectory) {
            $output->writeln('<error>Make sure the container has write permissions to "storage/database" and "storage/files" on the host system</error>');

            return;
        }

        $this->resourceUsage->startTimer();

        $output->writeln('Running database migrations...');
        $consoleApplication = $this->getConsoleApplication();
        $this->migrationRunner->run(
            application: $consoleApplication,
            output: $output
        );

        $this->commandBus->dispatch(new ImportAthlete($output));
        $this->commandBus->dispatch(new ImportGear($output));
        $this->commandBus->dispatch(new ImportActivities($output));
        $this->commandBus->dispatch(new LinkCustomGearToActivities($output));
        $this->commandBus->dispatch(new ImportActivitySplits($output));
        $this->commandBus->dispatch(new ImportActivityLaps($output));
        $this->commandBus->dispatch(new ImportActivityStreams($output));
        $this->commandBus->dispatch(new CalculateBestActivityEfforts($output));
        $this->commandBus->dispatch(new ImportSegments($output));
        $this->commandBus->dispatch(new ImportChallenges($output));
        $this->commandBus->dispatch(new CalculateBestStreamAverages($output));
        $this->commandBus->dispatch(new CalculateNormalizedPower($output));
        $this->commandBus->dispatch(new CalculateCombinedStreams($output));

        if ($rateLimits = $this->strava->getRateLimit()) {
            $output->title('STRAVA API RATE LIMITS');
            $output->listing([
                sprintf('15 min rate: %s/%s', $rateLimits->getFifteenMinRateUsage(), $rateLimits->getFifteenMinRateLimit()),
                sprintf('15 min read rate: %s/%s', $rateLimits->getFifteenMinReadRateUsage(), $rateLimits->getFifteenMinReadRateLimit()),
                sprintf('daily rate: %s/%s', $rateLimits->getDailyRateUsage(), $rateLimits->getDailyRateLimit()),
                sprintf('daily read rate: %s/%s', $rateLimits->getDailyReadRateUsage(), $rateLimits->getDailyReadRateLimit()),
            ]);
        }

        $this->connection->executeStatement('VACUUM');
        $output->writeln('Database got vacuumed 🧹');

        $this->resourceUsage->stopTimer();
        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));
    }

    public function runBuild(SymfonyStyle $output): void
    {
        $consoleApplication = $this->getConsoleApplication();
        if (!$this->migrationRunner->isAtLatestVersion($consoleApplication)) {
            $output->writeln('<error>Your database is not up to date with the migration schema. Run the import command before building the HTML files</error>');

            return;
        }
        if (!$this->stravaDataImportStatus->isCompleted()) {
            $output->writeln('<error>Wait until all Strava data has been imported before building the app</error>');

            return;
        }
        $this->resourceUsage->startTimer();

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

        $this->resourceUsage->stopTimer();
        $this->commandBus->dispatch(new SendNotification(
            title: 'Build successful',
            message: sprintf('New build of your Strava stats was successful in %ss', $this->resourceUsage->getRunTimeInSeconds()),
            tags: ['+1'],
            actionUrl: $this->appUrl
        ));

        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));
    }
}
