<?php

namespace App\Application\Import\ProcessRawActivityData\Pipeline;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\Lap\ActivityLap;
use App\Domain\Activity\Lap\ActivityLapId;
use App\Domain\Activity\Lap\ActivityLapRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ProcessActivityLaps implements ProcessRawDataStep
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private ActivityLapRepository $activityLapRepository,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $countLapsAdded = 0;
        $countActivitiesProcessed = 0;
        foreach ($this->activityRepository->findActivityIds() as $activityId) {
            $activityWithRawData = $this->activityWithRawDataRepository->find($activityId);
            if (!$activityWithRawData->hasLaps()) {
                continue;
            }
            if ($this->activityLapRepository->isImportedForActivity($activityId)) {
                continue;
            }

            ++$countActivitiesProcessed;

            foreach ($activityWithRawData->getLaps() as $lap) {
                $this->activityLapRepository->add(ActivityLap::create(
                    lapId: ActivityLapId::fromUnprefixed((string) $lap['id']),
                    activityId: $activityId,
                    lapNumber: $lap['lap_index'],
                    name: $lap['name'],
                    elapsedTimeInSeconds: $lap['elapsed_time'],
                    movingTimeInSeconds: $lap['moving_time'],
                    distance: Meter::from($lap['distance']),
                    averageSpeed: MetersPerSecond::from($lap['average_speed']),
                    minAverageSpeed: MetersPerSecond::from($lap['min_average_speed']),
                    maxAverageSpeed: MetersPerSecond::from($lap['max_average_speed']),
                    maxSpeed: MetersPerSecond::from($lap['max_speed']),
                    elevationDifference: Meter::from($lap['total_elevation_gain'] ?? 0),
                    averageHeartRate: !empty($lap['average_heartrate']) ? (int) round($lap['average_heartrate']) : null,
                ));
                ++$countLapsAdded;
            }
        }
        $output->writeln(sprintf('  => Added %d new activity laps for %d activities', $countLapsAdded, $countActivitiesProcessed));
    }
}
