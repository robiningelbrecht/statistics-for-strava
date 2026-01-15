<?php

namespace App\Domain\Activity;

use App\Domain\Activity\Route\RouteGeography;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Gear\GearId;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class DbalActivityRepository implements ActivityRepository
{
    /** @var array<int|string, Activities> */
    public static array $cachedActivities = [];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function find(ActivityId $activityId): Activity
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Activity')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Activity "%s" not found', $activityId));
        }

        return $this->hydrate($result);
    }

    public function findSummary(ActivityId $activityId): ActivitySummary
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('name, startDateTime')
            ->from('Activity')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Activity "%s" not found', $activityId));
        }

        return ActivitySummary::create(
            name: $result['name'],
            startDateTime: SerializableDateTime::fromString($result['startDateTime']),
        );
    }

    public function findAll(?int $limit = null): Activities
    {
        $cacheKey = $limit ?? 'all';
        if (array_key_exists((string) $cacheKey, DbalActivityRepository::$cachedActivities)) {
            return DbalActivityRepository::$cachedActivities[$cacheKey];
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC')
            ->setMaxResults($limit);

        $activities = array_map(
            $this->hydrate(...),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        );
        DbalActivityRepository::$cachedActivities[$cacheKey] = Activities::fromArray($activities);

        return DbalActivityRepository::$cachedActivities[$cacheKey];
    }

    public function findByStartDate(SerializableDateTime $startDate, ?ActivityType $activityType): Activities
    {
        // @TODO: Add static cache to this call.
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
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

        return Activities::fromArray(array_map(
            $this->hydrate(...),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findBySportTypes(SportTypes $sportTypes): Activities
    {
        // @TODO: Add static cache to this call.
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Activity')
            ->andWhere('sportType IN (:sportTypes)')
            ->setParameter(
                key: 'sportTypes',
                value: $sportTypes->map(fn (SportType $sportType) => $sportType->value),
                type: ArrayParameterType::STRING
            )
            ->orderBy('startDateTime', 'DESC');

        return Activities::fromArray(array_map(
            $this->hydrate(...),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): Activity
    {
        return Activity::fromState(
            activityId: ActivityId::fromString($result['activityId']),
            startDateTime: SerializableDateTime::fromString($result['startDateTime']),
            sportType: SportType::from($result['sportType']),
            worldType: WorldType::from($result['worldType']),
            name: $result['name'],
            description: $result['description'] ?: '',
            distance: Meter::from($result['distance'])->toKilometer(),
            elevation: Meter::from($result['elevation'] ?: 0),
            startingCoordinate: Coordinate::createFromOptionalLatAndLng(
                Latitude::fromOptionalString((string) $result['startingCoordinateLatitude']),
                Longitude::fromOptionalString((string) $result['startingCoordinateLongitude'])
            ),
            calories: (int) ($result['calories'] ?? 0),
            averagePower: ((int) $result['averagePower']) ?: null,
            maxPower: ((int) $result['maxPower']) ?: null,
            averageSpeed: KmPerHour::from($result['averageSpeed']),
            maxSpeed: KmPerHour::from($result['maxSpeed']),
            averageHeartRate: isset($result['averageHeartRate']) ? (int) round($result['averageHeartRate']) : null,
            maxHeartRate: isset($result['maxHeartRate']) ? (int) round($result['maxHeartRate']) : null,
            averageCadence: isset($result['averageCadence']) ? (int) round($result['averageCadence']) : null,
            movingTimeInSeconds: $result['movingTimeInSeconds'] ?: 0,
            kudoCount: $result['kudoCount'] ?: 0,
            deviceName: $result['deviceName'],
            totalImageCount: $result['totalImageCount'] ?: 0,
            localImagePaths: $result['localImagePaths'] ? explode(',', (string) $result['localImagePaths']) : [],
            polyline: $result['polyline'],
            routeGeography: RouteGeography::create(Json::decode($result['routeGeography'] ?? '[]')),
            weather: $result['weather'],
            gearId: GearId::fromOptionalString($result['gearId']),
            isCommute: (bool) $result['isCommute'],
            workoutType: WorkoutType::tryFrom($result['workoutType'] ?? ''),
        );
    }
}
