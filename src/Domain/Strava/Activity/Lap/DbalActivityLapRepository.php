<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Lap;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;

final readonly class DbalActivityLapRepository extends DbalRepository implements ActivityLapRepository
{
    public function findBy(ActivityId $activityId): ActivityLaps
    {
        $sql = 'SELECT * FROM ActivityLap 
         WHERE activityId = :activityId
         ORDER BY lapNumber ASC';

        $results = $this->connection->executeQuery($sql, [
            'activityId' => $activityId,
        ])->fetchAllAssociative();

        return ActivityLaps::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $results
        ));
    }

    public function add(ActivityLap $lap): void
    {
        $sql = 'INSERT INTO ActivityLap (
            lapId, activityId, lapNumber, name, elapsedTimeInSeconds, movingTimeInSeconds, distance,
            averageSpeed, maxSpeed, elevationDifference, averageHeartRate
        ) VALUES(
            :lapId, :activityId, :lapNumber, :name, :elapsedTimeInSeconds, :movingTimeInSeconds, :distance,
            :averageSpeed, :maxSpeed, :elevationDifference, :averageHeartRate
        )';

        $this->connection->executeStatement($sql, [
            'lapId' => $lap->getLapId(),
            'activityId' => $lap->getActivityId(),
            'lapNumber' => $lap->getLapNumber(),
            'name' => $lap->getName(),
            'elapsedTimeInSeconds' => $lap->getElapsedTimeInSeconds(),
            'movingTimeInSeconds' => $lap->getMovingTimeInSeconds(),
            'distance' => $lap->getDistance()->toInt(),
            'averageSpeed' => $lap->getAverageSpeed()->toFloat(),
            'maxSpeed' => $lap->getMaxSpeed()->toFloat(),
            'elevationDifference' => $lap->getElevationDifference()->toInt(),
            'averageHeartRate' => $lap->getAverageHeartRate(),
        ]);
    }

    public function isImportedForActivity(ActivityId $activityId): bool
    {
        return $this->connection
                ->executeQuery('SELECT COUNT(*) FROM ActivityLap WHERE activityId = :activityId', [
                    'activityId' => $activityId,
                ])
                ->fetchOne() > 0;
    }

    public function deleteForActivity(ActivityId $activityId): void
    {
        $sql = 'DELETE FROM ActivityLap WHERE activityId = :activityId';

        $this->connection->executeStatement($sql, [
            'activityId' => $activityId,
        ]);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): ActivityLap
    {
        return ActivityLap::fromState(
            lapId: ActivityLapId::fromString($result['lapId']),
            activityId: ActivityId::fromString($result['activityId']),
            lapNumber: $result['lapNumber'],
            name: $result['name'],
            elapsedTimeInSeconds: $result['elapsedTimeInSeconds'],
            movingTimeInSeconds: $result['movingTimeInSeconds'],
            distance: Meter::from($result['distance']),
            averageSpeed: MetersPerSecond::from($result['averageSpeed']),
            maxSpeed: MetersPerSecond::from($result['maxSpeed']),
            elevationDifference: Meter::from($result['elevationDifference']),
            averageHeartRate: $result['averageHeartRate'],
        );
    }
}
