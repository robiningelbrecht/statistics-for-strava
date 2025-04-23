<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Split\ImportActivitySplits;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\Split\ActivitySplit;
use App\Domain\Strava\Activity\Split\ActivitySplitRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;

final readonly class ImportActivitySplitsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private ActivitySplitRepository $activitySplitRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportActivitySplits);

        $command->getOutput()->writeln('Importing activity splits...');

        $countSplitsAdded = 0;
        $countActivitiesProcessed = 0;
        foreach ($this->activityRepository->findActivityIds() as $activityId) {
            $activityWithRawData = $this->activityWithRawDataRepository->find($activityId);
            if (!$activityWithRawData->hasSplits()) {
                continue;
            }
            if ($this->activitySplitRepository->isImportedForActivity($activityId)) {
                continue;
            }

            ++$countActivitiesProcessed;

            foreach ($activityWithRawData->getSplits() as $split) {
                $this->activitySplitRepository->add(ActivitySplit::create(
                    activityId: $activityId,
                    unitSystem: UnitSystem::from($split['unit_system']),
                    splitNumber: $split['split'],
                    distance: Meter::from($split['distance']),
                    elapsedTimeInSeconds: $split['elapsed_time'],
                    movingTimeInSeconds: $split['moving_time'],
                    elevationDifference: Meter::from($split['elevation_difference'] ?? 0),
                    averageSpeed: MetersPerSecond::from($split['average_speed']),
                    minAverageSpeed: MetersPerSecond::from($split['min_average_speed']),
                    maxAverageSpeed: MetersPerSecond::from($split['max_average_speed']),
                    paceZone: $split['pace_zone'],
                ));
                ++$countSplitsAdded;
            }
        }
        $command->getOutput()->writeln(sprintf('  => Added %d new activity splits for %d activities', $countSplitsAdded, $countActivitiesProcessed));
    }
}
