<?php

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\Exception\EntityNotFound;
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

    public function findMarkedForDeletion(): ActivityIds
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
