<?php

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class DbalActivityIdRepository implements ActivityIdRepository
{
    public static ActivityIds $cachedActivityIds;

    public function __construct(
        private readonly Connection $connection,
    ) {
        self::$cachedActivityIds = ActivityIds::empty();
    }

    public function count(): int
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('COUNT(activityId)')
            ->from('Activity');

        return (int) $queryBuilder->executeQuery()->fetchOne();
    }

    public function findAll(): ActivityIds
    {
        if (!self::$cachedActivityIds->isEmpty()) {
            return self::$cachedActivityIds;
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('activityId')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC');

        $activityIds = ActivityIds::fromArray(array_map(
            ActivityId::fromString(...),
            $queryBuilder->executeQuery()->fetchFirstColumn()
        ));
        self::$cachedActivityIds = $activityIds;

        return self::$cachedActivityIds;
    }

    public function findByStartDate(SerializableDateTime $startDate, ?ActivityType $activityType): ActivityIds
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('activityId')
            ->from('Activity')
            ->andWhere('startDateTime BETWEEN :startDateTimeStart AND :startDateTimeEnd')
            ->setParameter(
                key: 'startDateTimeStart',
                value: $startDate->format('Y-m-d 00:00:00'),
            )
            ->setParameter(
                key: 'startDateTimeEnd',
                value: $startDate->format('Y-m-d 23:59:59'),
            )
            ->orderBy('startDateTime', 'DESC');

        if ($activityType) {
            $queryBuilder->andWhere('sportType IN (:sportTypes)')
                ->setParameter(
                    key: 'sportTypes',
                    value: array_map(fn (SportType $sportType) => $sportType->value, $activityType->getSportTypes()->toArray()),
                    type: ArrayParameterType::STRING
                );
        }

        return ActivityIds::fromArray(array_map(
            ActivityId::fromString(...),
            $queryBuilder->executeQuery()->fetchFirstColumn()
        ));
    }

    public function findBySportTypes(SportTypes $sportTypes): ActivityIds
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('activityId')
            ->from('Activity')
            ->andWhere('sportType IN (:sportTypes)')
            ->setParameter(
                key: 'sportTypes',
                value: $sportTypes->map(fn (SportType $sportType) => $sportType->value),
                type: ArrayParameterType::STRING
            )
            ->orderBy('startDateTime', 'DESC');

        return ActivityIds::fromArray(array_map(
            ActivityId::fromString(...),
            $queryBuilder->executeQuery()->fetchFirstColumn()
        ));
    }

    public function hasForSportTypes(SportTypes $sportTypes): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('COUNT(activityId)')
            ->from('Activity')
            ->andWhere('sportType IN (:sportTypes)')
            ->setParameter(
                key: 'sportTypes',
                value: array_map(fn (SportType $sportType) => $sportType->value, $sportTypes->toArray()),
                type: ArrayParameterType::STRING
            );

        return (bool) $queryBuilder->executeQuery()->fetchOne();
    }

    public function findUniqueStravaGearIds(?ActivityIds $restrictToActivityIds): GearIds
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('DISTINCT JSON_EXTRACT(data, "$.gear_id") as stravaGearId')
            ->from('Activity')
            ->andWhere('stravaGearId IS NOT NULL');

        if ($restrictToActivityIds && !$restrictToActivityIds->isEmpty()) {
            $queryBuilder->andWhere('activityId IN (:activityIds)');
            $queryBuilder->setParameter(
                key: 'activityIds',
                value: array_map(strval(...), $restrictToActivityIds->toArray()),
                type: ArrayParameterType::STRING
            );
        }

        return GearIds::fromArray(array_map(
            GearId::fromUnprefixed(...),
            $queryBuilder->executeQuery()->fetchFirstColumn(),
        ));
    }

    public function findActivityIdsMarkedForDeletion(): ActivityIds
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('activityId')
            ->from('Activity')
            ->where('markedForDeletion = 1');

        return ActivityIds::fromArray(array_map(
            ActivityId::fromString(...),
            $queryBuilder->executeQuery()->fetchFirstColumn(),
        ));
    }

    public function findLongestFor(Years $years): ActivityId
    {
        if (!$result = $this->connection->executeQuery(
            <<<SQL
                SELECT activityId
                FROM Activity
                WHERE strftime('%Y',startDateTime) IN (:years)
                ORDER BY movingTimeInSeconds DESC
                LIMIT 1
            SQL,
            [
                'years' => array_map(strval(...), $years->toArray()),
            ],
            [
                'years' => ArrayParameterType::STRING,
            ]
        )->fetchOne()) {
            throw new EntityNotFound('Could not determine longest activity');
        }

        return ActivityId::fromString($result);
    }
}
