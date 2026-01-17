<?php

namespace App\Domain\Activity;

use App\Domain\Activity\Route\RouteGeography;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Activity\Stream\ActivityPowerRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Domain\Gear\CustomGear\CustomGearConfig;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\Maintenance\GearMaintenanceConfig;
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

final class DbalEnrichedActivityRepository implements ActivityRepository
{
    /** @var array<string, Activity> */
    public static array $cachedActivities = [];
    /** @var array<string, Activities> */
    public static array $cachedActivitiesPerActivityType;

    public function __construct(
        private readonly Connection $connection,
        private readonly ActivityPowerRepository $activityPowerRepository,
        private readonly ActivityStreamRepository $activityStreamRepository,
        private readonly ActivityTypeRepository $activityTypeRepository,
        private readonly GearRepository $gearRepository,
        private readonly GearMaintenanceConfig $gearMaintenanceConfig,
        private readonly CustomGearConfig $customGearConfig,
    ) {
    }

    private function enrichAll(): void
    {
        if (!empty(self::$cachedActivities)) {
            return;
        }

        $maintenanceTags = $this->gearMaintenanceConfig->getAllMaintenanceTags();
        $customGearTags = $this->customGearConfig->getAllGearTags();
        $gears = $this->gearRepository->findAll();

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC');

        $results = $queryBuilder->executeQuery()->fetchAllAssociative();

        foreach ($results as $result) {
            $activity = $this->hydrate($result);
            if ($gearId = $activity->getGearId()) {
                $activity->enrichWithGearName($gears->getByGearId($gearId)?->getName());
            }
            $bestPowerOutputs = $this->activityPowerRepository->findBest($activity->getId());
            if (!$bestPowerOutputs->isEmpty()) {
                $activity->enrichWithBestPowerOutputs($bestPowerOutputs);
            }

            $activity->enrichWithNormalizedPower(
                $this->activityPowerRepository->findNormalizedPower($activity->getId())
            );
            $activity->enrichWithTags([
                ...$maintenanceTags,
                ...$customGearTags,
            ]);

            try {
                $cadenceStream = $this->activityStreamRepository->findOneByActivityAndStreamType(
                    activityId: $activity->getId(),
                    streamType: StreamType::CADENCE
                );

                if (!empty($cadenceStream->getData())) {
                    $activity->enrichWithMaxCadence(max($cadenceStream->getData()));
                }
            } catch (EntityNotFound) {
            }

            self::$cachedActivities[(string) $activity->getId()] = $activity;
        }
    }

    public function find(ActivityId $activityId): Activity
    {
        $this->enrichAll();

        return self::$cachedActivities[(string) $activityId] ?? throw new EntityNotFound(sprintf('Activity "%s" not found', $activityId));
    }

    public function findAll(): Activities
    {
        $this->enrichAll();

        return Activities::fromArray(self::$cachedActivities);
    }

    public function findByStartDate(SerializableDateTime $startDate, ?ActivityType $activityType): Activities
    {
        $this->enrichAll();

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

        $activityIds = $queryBuilder->executeQuery()->fetchFirstColumn();

        $activities = Activities::empty();
        foreach ($activityIds as $activityId) {
            $activities->add(self::$cachedActivities[(string) $activityId]);
        }

        return $activities;
    }

    public function findBySportTypes(SportTypes $sportTypes): Activities
    {
        $this->enrichAll();

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

        $activityIds = $queryBuilder->executeQuery()->fetchFirstColumn();

        $activities = Activities::empty();
        foreach ($activityIds as $activityId) {
            $activities->add(self::$cachedActivities[(string) $activityId]);
        }

        return $activities;
    }

    public function findGroupedByActivityType(): array
    {
        if (empty(self::$cachedActivitiesPerActivityType)) {
            $this->enrichAll();
            $activityTypes = $this->activityTypeRepository->findAll();
            $allActivities = $this->findAll();

            /** @var ActivityType $activityType */
            foreach ($activityTypes as $activityType) {
                self::$cachedActivitiesPerActivityType[$activityType->value] = $allActivities->filterOnActivityType($activityType);
            }
        }

        return self::$cachedActivitiesPerActivityType;
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
