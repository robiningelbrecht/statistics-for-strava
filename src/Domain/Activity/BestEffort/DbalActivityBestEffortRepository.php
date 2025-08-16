<?php

declare(strict_types=1);

namespace App\Domain\Activity\BestEffort;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class DbalActivityBestEffortRepository extends DbalRepository implements ActivityBestEffortRepository
{
    public function __construct(
        Connection $connection,
        private ActivityRepository $activityRepository,
    ) {
        parent::__construct($connection);
    }

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

    public function findAll(): ActivityBestEfforts
    {
        return $this->findBestEffortsForSportTypes(SportTypes::fromArray(SportType::cases()));
    }

    public function findBestEffortsFor(ActivityType $activityType): ActivityBestEfforts
    {
        return $this->findBestEffortsForSportTypes($activityType->getSportTypes());
    }

    private function findBestEffortsForSportTypes(SportTypes $sportTypes): ActivityBestEfforts
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

        $results = $this->connection->executeQuery($sql,
            [
                'sportTypes' => array_unique(array_map(fn (SportType $sportType) => $sportType->value, $sportTypes->toArray())),
            ],
            [
                'sportTypes' => ArrayParameterType::STRING,
            ]
        )->fetchAllAssociative();

        $activityBestEfforts = ActivityBestEfforts::empty();

        foreach ($results as $result) {
            $activityId = ActivityId::fromString($result['activityId']);
            $activityBestEffort = ActivityBestEffort::fromState(
                activityId: $activityId,
                distanceInMeter: Meter::from($result['distanceInMeter']),
                sportType: SportType::from($result['sportType']),
                timeInSeconds: $result['timeInSeconds']
            );

            try {
                $activityBestEffort->enrichWithActivity($this->activityRepository->find($activityId));
            } catch (EntityNotFound) {
            }
            $activityBestEfforts->add($activityBestEffort);
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

    public function deleteForActivity(ActivityId $activityId): void
    {
        $sql = 'DELETE FROM ActivityBestEffort WHERE activityId = :activityId';

        $this->connection->executeStatement($sql, [
            'activityId' => $activityId,
        ]);
    }
}
