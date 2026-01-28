<?php

declare(strict_types=1);

namespace App\Domain\Activity\BestEffort;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\EnrichedActivities;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\DateRange;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class DbalActivityBestEffortRepository extends DbalRepository implements ActivityBestEffortRepository
{
    public function __construct(
        Connection $connection,
        private EnrichedActivities $enrichedActivities,
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
        return $this->findBestEffortsForSportTypes(
            sportTypes: SportTypes::fromArray(SportType::cases()),
            dateRange: DateRange::upUntilNow()
        );
    }

    public function hasData(): bool
    {
        $sql = 'SELECT 1 FROM ActivityBestEffort LIMIT 1';

        return (bool) $this->connection->executeQuery($sql)->fetchOne();
    }

    public function findBestEffortsFor(ActivityType $activityType, DateRange $dateRange): ActivityBestEfforts
    {
        return $this->findBestEffortsForSportTypes(
            sportTypes: $activityType->getSportTypes(),
            dateRange: $dateRange
        );
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
            $activityId = ActivityId::fromString($result['activityId']);
            $activityBestEffort = ActivityBestEffort::fromState(
                activityId: $activityId,
                distanceInMeter: Meter::from($result['distanceInMeter']),
                sportType: SportType::from($result['sportType']),
                timeInSeconds: $result['timeInSeconds']
            );

            try {
                $activityBestEffort->enrichWithActivity($this->enrichedActivities->find($activityId));
            } catch (EntityNotFound) {
                // continue;
            }
            $activityBestEfforts->add($activityBestEffort);
        }

        return $activityBestEfforts;
    }

    private function findBestEffortsForSportTypes(
        SportTypes $sportTypes,
        DateRange $dateRange,
    ): ActivityBestEfforts {
        $sql = 'SELECT
                activityId,
                sportType,
                distanceInMeter,
                timeInSeconds
                FROM (
                    SELECT
                        ActivityBestEffort.activityId,
                        ActivityBestEffort.sportType,
                        distanceInMeter,
                        timeInSeconds,
                        ROW_NUMBER() OVER (
                            PARTITION BY ActivityBestEffort.sportType, distanceInMeter
                            ORDER BY timeInSeconds ASC
                        ) AS rn
                    FROM ActivityBestEffort
                    INNER JOIN Activity ON ActivityBestEffort.activityId = Activity.activityId
                    WHERE ActivityBestEffort.sportType IN (:sportTypes)
                    AND startDateTime BETWEEN :dateFrom AND :dateTo
                ) ranked
                WHERE rn = 1
                ORDER BY distanceInMeter ASC';

        $results = $this->connection->executeQuery(
            $sql,
            [
                'sportTypes' => array_unique(array_map(fn (SportType $sportType) => $sportType->value, $sportTypes->toArray())),
                'dateFrom' => $dateRange->getFrom()->format('Y-m-d 00:00:00'),
                'dateTo' => $dateRange->getTill()->format('Y-m-d 23:59:59'),
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
                $activityBestEffort->enrichWithActivity($this->enrichedActivities->find($activityId));
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
