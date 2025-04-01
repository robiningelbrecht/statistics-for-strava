<?php

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Gear\GearId;
use App\Infrastructure\Eventing\EventBus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Geocoding\Nominatim\Location;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final class DbalActivityRepository implements ActivityRepository
{
    /** @var array<int|string, Activities> */
    public static array $cachedActivities = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly EventBus $eventBus,
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

    public function count(): int
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('COUNT(*)')
            ->from('Activity');

        return (int) $queryBuilder->executeQuery()->fetchOne();
    }

    public function findAll(?int $limit = null): Activities
    {
        $cacheKey = $limit ?? 'all';
        if (array_key_exists($cacheKey, DbalActivityRepository::$cachedActivities)) {
            return DbalActivityRepository::$cachedActivities[$cacheKey];
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC')
            ->setMaxResults($limit);

        $activities = array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        );
        DbalActivityRepository::$cachedActivities[$cacheKey] = Activities::fromArray($activities);

        return DbalActivityRepository::$cachedActivities[$cacheKey];
    }

    public function delete(Activity $activity): void
    {
        $sql = 'DELETE FROM Activity 
        WHERE activityId = :activityId';

        $this->connection->executeStatement($sql, [
            'activityId' => $activity->getId(),
        ]);

        $this->eventBus->publishEvents($activity->getRecordedEvents());
    }

    public function findActivityIds(): ActivityIds
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('activityId')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC');

        return ActivityIds::fromArray(array_map(
            fn (string $id) => ActivityId::fromString($id),
            $queryBuilder->executeQuery()->fetchFirstColumn(),
        ));
    }

    public function findActivityIdsThatNeedStreamImport(): ActivityIds
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('activityId')
            ->from('Activity')
            ->where('streamsAreImported = 0 OR streamsAreImported IS NULL')
            ->orderBy('startDateTime', 'DESC');

        return ActivityIds::fromArray(array_map(
            fn (string $id) => ActivityId::fromString($id),
            $queryBuilder->executeQuery()->fetchFirstColumn(),
        ));
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): Activity
    {
        $location = Json::decode($result['location'] ?? '[]');

        return Activity::fromState(
            activityId: ActivityId::fromString($result['activityId']),
            startDateTime: SerializableDateTime::fromString($result['startDateTime']),
            sportType: SportType::from($result['sportType']),
            name: $result['name'],
            description: $result['description'] ?: '',
            distance: Kilometer::from($result['distance'] / 1000),
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
            localImagePaths: $result['localImagePaths'] ? explode(',', $result['localImagePaths']) : [],
            polyline: $result['polyline'],
            location: $location ? Location::fromState($location) : null,
            weather: $result['weather'],
            gearId: GearId::fromOptionalString($result['gearId']),
            gearName: $result['gearName'],
            isCommute: (bool) $result['isCommute'],
            workoutType: WorkoutType::tryFrom($result['workoutType'] ?? ''),
        );
    }
}
