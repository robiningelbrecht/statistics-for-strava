<?php

namespace App\Application\Import\ProcessRawActivityData\Pipeline;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\Split\ActivitySplit;
use App\Domain\Activity\Split\ActivitySplitRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ProcessActivitySplits implements ProcessRawDataStep
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private ActivitySplitRepository $activitySplitRepository,
    ) {
    }

    public function process(OutputInterface $output): void
    {
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
        $output->writeln(sprintf('  => Added %d new activity splits for %d activities', $countSplitsAdded, $countActivitiesProcessed));
    }
}
