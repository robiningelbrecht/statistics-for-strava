<?php

declare(strict_types=1);

namespace App\Domain\Activity\BestEffort;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\StreamType;
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

    public function hasData(): bool
    {
        $sql = 'SELECT 1 FROM ActivityBestEffort LIMIT 1';

        return (bool) $this->connection->executeQuery($sql)->fetchOne();
    }

    public function findBestEffortHistory(ActivityType $activityType): ActivityBestEfforts
    {
        $sql = 'SELECT
            activityId,
            distanceInMeter,
            sportType,
            timeInSeconds
        FROM (
            SELECT
                activityId,
                distanceInMeter,
                sportType,
                timeInSeconds,
                ROW_NUMBER() OVER (
                    PARTITION BY sportType, distanceInMeter
                    ORDER BY timeInSeconds ASC
                ) AS rn
            FROM ActivityBestEffort
            WHERE sportType IN (:sportTypes)
        ) ranked
        WHERE rn <= 10
        ORDER BY sportType, distanceInMeter, rn';

        $results = $this->connection->executeQuery(
            $sql,
            [
                'sportTypes' => array_unique(
                    array_map(
                        fn (SportType $sportType) => $sportType->value,
                        $activityType->getSportTypes()->toArray()
                    )
                ),
            ],
            [
                'sportTypes' => ArrayParameterType::STRING,
            ]
        )->fetchAllAssociative();

        $activityBestEfforts = ActivityBestEfforts::empty();

        foreach ($results as $result) {
            $activityBestEfforts->add(ActivityBestEffort::fromState(
                activityId: ActivityId::fromString($result['activityId']),
                distanceInMeter: Meter::from($result['distanceInMeter']),
                sportType: SportType::from($result['sportType']),
                timeInSeconds: $result['timeInSeconds']
            ));
        }

        return $activityBestEfforts;
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
            ActivityId::fromString(...),
            $this->connection->executeQuery(
                $sql,
                [
                    'timeStreamType' => StreamType::TIME->value,
                    'distanceStreamType' => StreamType::DISTANCE->value,
                    'sportTypes' => array_map(
                        fn (SportType $sportType) => $sportType->value,
                        array_filter(SportType::cases(), fn (SportType $sportType): bool => $sportType->supportsBestEffortsStats())
                    ),
                ],
                [
                    'sportTypes' => ArrayParameterType::STRING,
                ]
            )->fetchFirstColumn()
        ));
    }

    public function deleteForActivity(ActivityId $activityId): void
    {
        $sql = 'DELETE FROM ActivityBestEffort WHERE activityId = :activityId';

        $this->connection->executeStatement($sql, [
            'activityId' => $activityId,
        ]);
    }
}
