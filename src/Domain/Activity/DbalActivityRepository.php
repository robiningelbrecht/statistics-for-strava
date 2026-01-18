<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Domain\Activity\Route\RouteGeography;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Gear\GearId;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final class DbalActivityRepository implements ActivityRepository
{
    /** @var array<string, Activity> */
    private array $cachedActivities = [];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function find(ActivityId $activityId): Activity
    {
        if (empty($this->cachedActivities)) {
            // Do an initial cache of all activities.
            $queryBuilder = $this->connection->createQueryBuilder();
            $results = $queryBuilder->select('*')
                ->from('Activity')
                ->executeQuery()->fetchAllAssociative();

            foreach ($results as $result) {
                $this->cachedActivities[$result['activityId']] = $this->hydrate($result);
            }
        }

        if (array_key_exists((string) $activityId, $this->cachedActivities)) {
            return $this->cachedActivities[(string) $activityId];
        }

        // Check if the activity has been added after the initial cache build,
        // Ifo so, hydrate and add it to cache.
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Activity')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Activity "%s" not found', $activityId));
        }

        $this->cachedActivities[(string) $activityId] = $this->hydrate($result);

        return $this->cachedActivities[(string) $activityId];
    }

    public function findAll(): Activities
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('activityId')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC');

        return Activities::fromArray(array_map(
            fn (string $activityId): Activity => $this->find(ActivityId::fromString($activityId)),
            $queryBuilder->executeQuery()->fetchFirstColumn()
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
