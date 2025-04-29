<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BestEffort;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use Doctrine\DBAL\ArrayParameterType;

final readonly class DbalActivityBestEffortRepository extends DbalRepository implements ActivityBestEffortRepository
{
    public function add(ActivityBestEffort $activityBestEffort): void
    {
        $sql = 'INSERT INTO ActivityBestEffort (activityId, sportType, distanceInMeter, timeInSeconds)
        VALUES (:activityId, :sportType, :distanceInMeter, :timeInSeconds)';

        $this->connection->executeStatement($sql, [
            'activityId' => $activityBestEffort->getActivityId(),
            'sportType' => $activityBestEffort->getSportType()->value,
            'distanceInMeter' => $activityBestEffort->getDistanceInMeter()->toInt(),
            'timeInSeconds' => $activityBestEffort->getTimeInSeconds(),
        ]);
    }

    public function findBestEffortsFor(ActivityType $activityType): ActivityBestEfforts
    {
        $sql = 'WITH BestEfforts AS (
                    SELECT distanceInMeter, MIN(timeInSeconds) AS bestTime, sportType
                    FROM ActivityBestEffort
                    WHERE sportType IN (:sportTypes)
                    GROUP BY distanceInMeter, sportType
                ),
                RankedEfforts AS (
                    SELECT 
                        a.activityId, 
                        a.sportType, 
                        a.distanceInMeter, 
                        a.timeInSeconds,
                        ROW_NUMBER() OVER (
                            PARTITION BY a.distanceInMeter, a.sportType 
                            ORDER BY a.activityId
                        ) AS rowNumber
                    FROM ActivityBestEffort a
                    JOIN BestEfforts b 
                        ON a.distanceInMeter = b.distanceInMeter
                    AND a.timeInSeconds = b.bestTime
                    AND a.sportType = b.sportType
                    WHERE a.sportType IN (:sportTypes)
                )
                SELECT activityId, sportType, distanceInMeter, timeInSeconds
                FROM RankedEfforts
                WHERE rowNumber = 1
                ORDER BY distanceInMeter ASC';

        return ActivityBestEfforts::fromArray(array_map(
            fn (array $result) => ActivityBestEffort::fromState(
                activityId: ActivityId::fromString($result['activityId']),
                distanceInMeter: Meter::from($result['distanceInMeter']),
                sportType: SportType::from($result['sportType']),
                timeInSeconds: $result['timeInSeconds']
            ),
            $this->connection->executeQuery($sql,
                [
                    'sportTypes' => array_unique($activityType->getSportTypes()->map(fn (SportType $sportType) => $sportType->value)),
                ],
                [
                    'sportTypes' => ArrayParameterType::STRING,
                ]
            )->fetchAllAssociative()
        ));
    }

    public function findActivityIdsThatNeedBestEffortsCalculation(): ActivityIds
    {
        $sql = 'SELECT Activity.activityId FROM Activity 
                  WHERE sportType IN (:sportTypes)
                  AND NOT EXISTS (
                    SELECT 1 FROM ActivityBestEffort WHERE ActivityBestEffort.activityId = Activity.activityId
                  )
                  AND EXISTS (
                    SELECT 1 FROM ActivityStream x
                    WHERE x.activityId = Activity.activityId AND x.streamType = :timeStreamType AND json_array_length(x.data) > 0
                  )
                  AND EXISTS (
                    SELECT 1 FROM ActivityStream y
                    WHERE y.activityId = Activity.activityId AND y.streamType = :distanceStreamType AND json_array_length(y.data) > 0
                  )';

        return ActivityIds::fromArray(array_map(
            fn (string $activityId) => ActivityId::fromString($activityId),
            $this->connection->executeQuery($sql,
                [
                    'timeStreamType' => StreamType::TIME->value,
                    'distanceStreamType' => StreamType::DISTANCE->value,
                    'sportTypes' => array_map(
                        fn (SportType $sportType) => $sportType->value,
                        array_filter(SportType::cases(), fn (SportType $sportType) => $sportType->supportsBestEffortsStats())
                    ),
                ],
                [
                    'sportTypes' => ArrayParameterType::STRING,
                ]
            )->fetchFirstColumn()
        ));
    }
}
