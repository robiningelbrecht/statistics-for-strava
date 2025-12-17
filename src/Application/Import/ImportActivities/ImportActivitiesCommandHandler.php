<?php

namespace App\Application\Import\ImportActivities;

use App\Application\Import\ImportActivities\Pipeline\ActivityImportContext;
use App\Application\Import\ImportActivities\Pipeline\ActivityImportPipeline;
use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityVisibility;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypesToImport;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\RateLimit\StravaRateLimitHasBeenReached;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Daemon\Mutex\LockName;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\DependencyInjection\Mutex\WithMutex;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\SerializableTimezone;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

#[WithMutex(lockName: LockName::IMPORT_DATA_OR_BUILD_APP)]
final readonly class ImportActivitiesCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private ActivityRepository $activityRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private NumberOfNewActivitiesToProcessPerImport $numberOfNewActivitiesToProcessPerImport,
        private SportTypesToImport $sportTypesToImport,
        private ActivityVisibilitiesToImport $activityVisibilitiesToImport,
        private ActivitiesToSkipDuringImport $activitiesToSkipDuringImport,
        private ?SkipActivitiesRecordedBefore $skipActivitiesRecordedBefore,
        private Mutex $mutex,
        private ActivityImportPipeline $activityImportPipeline,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportActivities);
        $command->getOutput()->writeln('Importing activities...');

        $this->strava->setConsoleOutput($command->getOutput());

        $allActivityIds = $this->activityRepository->findActivityIds();
        $activityIdsToDelete = array_combine(
            $allActivityIds->map(fn (ActivityId $activityId): string => (string) $activityId),
            $allActivityIds->toArray(),
        );

        try {
            if ($command->isFullImport()) {
                // No restriction on activity ids, we need to execute a full import.
                $stravaActivities = $this->strava->getActivities();
            } else {
                // Restriction on activity ids, we want to execute a partial import.
                $stravaActivities = array_map(
                    $this->strava->getActivity(...),
                    $command->getRestrictToActivityIds()->toArray()
                );
            }
        } catch (StravaRateLimitHasBeenReached $exception) {
            $command->getOutput()->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return;
        } catch (ClientException|RequestException $exception) {
            $command->getOutput()->writeln(sprintf('<error>Strava API threw error: %s</error>', $exception->getMessage()));

            return;
        }

        $countTotalStravaActivitiesToImport = count($stravaActivities);
        if ($command->isFullImport()) {
            $command->getOutput()->writeln(
                sprintf('Status: %d out of %d activities imported', count($allActivityIds), $countTotalStravaActivitiesToImport)
            );
        }

        $delta = 1;
        foreach ($stravaActivities as $rawStravaData) {
            if (!SportType::tryFrom($rawStravaData['sport_type'])) {
                $command->getOutput()->writeln(sprintf(
                    '  => Sport type "%s" not supported yet. <a href="https://github.com/robiningelbrecht/statistics-for-strava/issues/new?assignees=robiningelbrecht&labels=new+feature&projects=&template=feature_request.md&title=Add+support+for+sport+type+%s>Open a new GitHub issue</a> to if you want support for this sport type',
                    $rawStravaData['sport_type'],
                    $rawStravaData['sport_type']));
                continue;
            }

            $sportType = SportType::from($rawStravaData['sport_type']);
            if (!$this->sportTypesToImport->has($sportType)) {
                continue;
            }

            $activityVisibility = ActivityVisibility::from($rawStravaData['visibility']);
            if (!$this->activityVisibilitiesToImport->has($activityVisibility)) {
                continue;
            }

            if ($this->skipActivitiesRecordedBefore?->isAfterOrOn(SerializableDateTime::createFromFormat(
                format: Activity::DATE_TIME_FORMAT,
                datetime: $rawStravaData['start_date_local'],
                timezone: SerializableTimezone::default(),
            ))) {
                continue;
            }

            $activityId = ActivityId::fromUnprefixed((string) $rawStravaData['id']);
            if ($this->activitiesToSkipDuringImport->has($activityId)) {
                continue;
            }

            try {
                $isNewActivity = !$this->activityWithRawDataRepository->exists($activityId);
                if ($isNewActivity && $command->isFullImport()) {
                    // When a partial import is triggered we fetch the activity from Strava beforehand.
                    // We only need to fetch activity details when running a full import.
                    $rawStravaData = $this->strava->getActivity($activityId);
                }

                $context = ActivityImportContext::create(
                    activityId: $activityId,
                    rawStravaData: $rawStravaData,
                    isNewActivity: $isNewActivity,
                );

                $context = $this->activityImportPipeline->process($context);
            } catch (StravaRateLimitHasBeenReached $exception) {
                $command->getOutput()->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

                return;
            } catch (ClientException|RequestException $exception) {
                $command->getOutput()->writeln(sprintf('<error>Strava API threw error: %s</error>', $exception->getMessage()));

                return;
            }

            $activity = $context->getActivity() ?? throw new \RuntimeException('Activity not set on $context');
            if ($context->isNewActivity()) {
                $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
                    activity: $activity,
                    rawData: $context->getRawStravaData()
                ));

                $this->numberOfNewActivitiesToProcessPerImport->increaseNumberOfProcessedActivities();
            } else {
                $this->activityWithRawDataRepository->update(ActivityWithRawData::fromState(
                    activity: $activity,
                    rawData: [
                        ...$this->activityWithRawDataRepository->find($activity->getId())->getRawData(),
                        ...$rawStravaData,
                    ]
                ));
            }

            foreach ($context->getStreams() as $stream) {
                $this->activityStreamRepository->add($stream);
                $this->activityWithRawDataRepository->markActivityStreamsAsImported($activityId);
            }

            unset($activityIdsToDelete[(string) $context->getActivity()->getId()]);

            $command->getOutput()->writeln(sprintf(
                '  => [%d/%d] %s activity: "%s - %s"',
                $delta,
                $countTotalStravaActivitiesToImport,
                $context->isNewActivity() ? 'Imported' : 'Updated',
                $activity->getName(),
                $activity->getStartDate()->format('d-m-Y'))
            );

            if ($this->numberOfNewActivitiesToProcessPerImport->maxNumberProcessed()) {
                // Stop importing activities, we reached the max number to process for this batch.
                break;
            }

            $this->mutex->heartbeat();
            ++$delta;
        }

        if ($this->numberOfNewActivitiesToProcessPerImport->maxNumberProcessed()) {
            // Shortcut the process here to make sure no activities are deleted yet.
            return;
        }

        if (!$command->isFullImport()) {
            // Only delete activities during full imports to avoid accidental deletion of data.
            return;
        }

        if (empty($activityIdsToDelete)) {
            return;
        }

        if (count($activityIdsToDelete) === count($allActivityIds) && array_values($activityIdsToDelete) == $allActivityIds->toArray()) {
            throw new \RuntimeException('All activities appear to be marked for deletion. This seems like a configuration issue. Aborting to prevent data loss');
        }

        $this->activityWithRawDataRepository->markActivitiesForDeletion(ActivityIds::fromArray($activityIdsToDelete));

        foreach ($activityIdsToDelete as $activityId) {
            $activity = $this->activityRepository->find($activityId);

            $command->getOutput()->writeln(sprintf(
                '  => Activity "%s - %s" marked for deletion',
                $activity->getName(),
                $activity->getStartDate()->format('d-m-Y'))
            );
        }
    }
}
