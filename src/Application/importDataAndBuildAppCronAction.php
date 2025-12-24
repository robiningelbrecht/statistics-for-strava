<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\RunBuild\RunBuild;
use App\Application\RunImport\RunImport;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Integration\Notification\SendNotification\SendNotification;
use App\Domain\Strava\Webhook\WebhookAspectType;
use App\Domain\Strava\Webhook\WebhookEventRepository;
use App\Infrastructure\Console\ProvideConsoleIntro;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Daemon\Cron\RunnableCronAction;
use App\Infrastructure\Daemon\Mutex\LockName;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\DependencyInjection\Mutex\WithMutex;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Symfony\Component\Console\Style\SymfonyStyle;

#[WithMutex(lockName: LockName::IMPORT_DATA_OR_BUILD_APP)]
final readonly class importDataAndBuildAppCronAction implements RunnableCronAction
{
    use ProvideConsoleIntro;

    public function __construct(
        private CommandBus $commandBus,
        private WebhookEventRepository $webhookEventRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private ResourceUsage $resourceUsage,
        private AppUrl $appUrl,
        private Mutex $mutex,
        private MigrationRunner $migrationRunner,
    ) {
    }

    public function getId(): string
    {
        return 'importDataAndBuildApp';
    }

    public function requiresDatabaseSchemaToBeUpdated(): bool
    {
        return false;
    }

    public function getMutexTtl(): int
    {
        return 1800;
    }

    public function run(SymfonyStyle $output): void
    {
        $this->outputConsoleIntro($output);
        $this->migrationRunner->run($output);
        $this->mutex->acquireLock('importDataAndBuildAppCronAction');

        $this->doRun(
            output: $output,
            restrictToActivityIds: null
        );
    }

    public function runForWebhooks(
        SymfonyStyle $output,
    ): void {
        $this->migrationRunner->run($output);
        $this->mutex->acquireLock('importDataAndBuildAppCronAction');

        if (!$webhookEvents = $this->webhookEventRepository->grab()) {
            // No webhooks to process.
            $output->writeln('No webhook events left to process...');

            return;
        }

        $activityIdsToDelete = ActivityIds::empty();
        $createOrUpdateActivityIds = ActivityIds::empty();
        foreach ($webhookEvents as $webhookEvent) {
            $activityId = ActivityId::fromUnprefixed($webhookEvent->getObjectId());
            if (WebhookAspectType::DELETE === $webhookEvent->getAspectType()) {
                $activityIdsToDelete->add($activityId);
            } else {
                $createOrUpdateActivityIds->add($activityId);
            }
        }

        if (!$activityIdsToDelete->isEmpty()) {
            $this->activityWithRawDataRepository->markActivitiesForDeletion($activityIdsToDelete);
        }

        $this->doRun(
            output: $output,
            restrictToActivityIds: $createOrUpdateActivityIds
        );
    }

    private function doRun(
        SymfonyStyle $output,
        ?ActivityIds $restrictToActivityIds,
    ): void {
        $this->resourceUsage->startTimer();

        $this->commandBus->dispatch(new RunImport(
            output: $output,
            restrictToActivityIds: $restrictToActivityIds,
        ));
        $this->commandBus->dispatch(new RunBuild(
            output: $output,
        ));

        $this->resourceUsage->stopTimer();
        $this->commandBus->dispatch(new SendNotification(
            title: 'Build successful',
            message: sprintf('New import and build of your Strava stats was successful in %ss', $this->resourceUsage->getRunTimeInSeconds()),
            tags: ['+1'],
            actionUrl: $this->appUrl
        ));
        $this->mutex->releaseLock();

        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));
    }
}
