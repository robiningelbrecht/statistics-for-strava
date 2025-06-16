<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Lap\ImportActivityLaps;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\Lap\ActivityLap;
use App\Domain\Strava\Activity\Lap\ActivityLapId;
use App\Domain\Strava\Activity\Lap\ActivityLapRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;

final readonly class ImportActivityLapsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private ActivityLapRepository $activityLapRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportActivityLaps);

        $command->getOutput()->writeln('Importing activity laps...');

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

            foreach ($activityWithRawData->getLaps() as $lab) {
                $this->activityLapRepository->add(ActivityLap::create(
                    lapId: ActivityLapId::fromUnprefixed((string) $lab['id']),
                    activityId: $activityId,
                    lapNumber: $lab['lap_index'],
                    name: $lab['name'],
                    elapsedTimeInSeconds: $lab['elapsed_time'],
                    movingTimeInSeconds: $lab['moving_time'],
                    distance: Meter::from($lab['distance']),
                    averageSpeed: MetersPerSecond::from($lab['average_speed']),
                    maxSpeed: MetersPerSecond::from($lab['max_speed']),
                    elevationDifference: Meter::from($lab['elevation_difference'] ?? 0),
                    averageHeartRate: $lab['average_heartrate'] ? (int) round($lab['average_heartrate']) : null,
                ));
                ++$countLapsAdded;
            }
        }
        $command->getOutput()->writeln(sprintf('  => Added %d new activity laps for %d activities', $countLapsAdded, $countActivitiesProcessed));
    }
}
