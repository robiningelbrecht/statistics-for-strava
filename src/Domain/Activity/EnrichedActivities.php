<?php

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Activity\Stream\ActivityPowerRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricType;
use App\Domain\Activity\Stream\StreamType;
use App\Domain\Gear\CustomGear\CustomGearConfig;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\Maintenance\GearMaintenanceConfig;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class EnrichedActivities
{
    /** @var array<string, Activity> */
    public static array $cachedActivities = [];
    /** @var array<string, string[]> */
    public static array $cachedActivitiesPerActivityType;

    public function __construct(
        private readonly Connection $connection,
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityStreamRepository $activityStreamRepository,
        private readonly ActivityTypeRepository $activityTypeRepository,
        private readonly ActivityPowerRepository $activityPowerRepository,
        private readonly GearRepository $gearRepository,
        private readonly GearMaintenanceConfig $gearMaintenanceConfig,
        private readonly CustomGearConfig $customGearConfig,
    ) {
    }

    private function enrichAll(): void
    {
        if ([] !== self::$cachedActivities) {
            return;
        }

        $maintenanceTags = $this->gearMaintenanceConfig->getAllMaintenanceTags();
        $customGearTags = $this->customGearConfig->getAllGearTags();
        $gears = $this->gearRepository->findAll();

        $normalizedPowers = [];
        $results = $this->connection->executeQuery(
            'SELECT activityId, data FROM ActivityStreamMetric
             WHERE streamType = :streamType AND metricType = :metricType',
            [
                'streamType' => StreamType::WATTS->value,
                'metricType' => ActivityStreamMetricType::NORMALIZED_POWER->value,
            ]
        )->fetchAllAssociative();

        foreach ($results as $result) {
            $decoded = Json::uncompressAndDecode($result['data']);
            $normalizedPowers[$result['activityId']] = $decoded[0] ?? null;
        }

        $activityTypes = $this->activityTypeRepository->findAll();

        foreach ($activityTypes as $activityType) {
            self::$cachedActivitiesPerActivityType[$activityType->value] = [];
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('activityId')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC');

        $results = $queryBuilder->executeQuery()->fetchFirstColumn();

        foreach ($results as $result) {
            $activityId = ActivityId::fromString($result);
            $activity = $this->activityRepository->find($activityId);
            $activity = $activity
                ->withNormalizedPower($normalizedPowers[(string) $activityId] ?? null)
                ->withBestPowerOutputs(
                    $this->activityPowerRepository->findBest($activity->getId())
                )
                ->withGearName(
                    $gears->getByGearId($activity->getGearId())?->getName()
                )
                ->withTags([
                    ...$maintenanceTags,
                    ...$customGearTags,
                ]);

            try {
                $cadenceStream = $this->activityStreamRepository->findOneByActivityAndStreamType(
                    activityId: $activity->getId(),
                    streamType: StreamType::CADENCE
                );

                if ([] !== $cadenceStream->getData()) {
                    $activity = $activity->withMaxCadence(max($cadenceStream->getData()));
                }
            } catch (EntityNotFound) {
            }

            self::$cachedActivities[(string) $activity->getId()] = $activity;
            self::$cachedActivitiesPerActivityType[$activity->getSportType()->getActivityType()->value][] = (string) $activity->getId();
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

        if ($activityType instanceof ActivityType) {
            $queryBuilder->andWhere('sportType IN (:sportTypes)')
                ->setParameter(
                    key: 'sportTypes',
                    value: array_map(fn (SportType $sportType) => $sportType->value, $activityType->getSportTypes()->toArray()),
                    type: ArrayParameterType::STRING
                );
        }

        $activityIds = $queryBuilder->executeQuery()->fetchFirstColumn();

        return Activities::fromArray($this->resolveIds($activityIds));
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

        return Activities::fromArray($this->resolveIds($activityIds));
    }

    /**
     * @return array<string, Activities>
     */
    public function findGroupedByActivityType(): array
    {
        $this->enrichAll();

        $activitiesPerActivityType = [];
        foreach (self::$cachedActivitiesPerActivityType as $activityType => $ids) {
            $activitiesPerActivityType[$activityType] = Activities::fromArray($this->resolveIds($ids));
        }

        return $activitiesPerActivityType;
    }

    /**
     * @param string[] $ids
     *
     * @return Activity[]
     */
    private function resolveIds(array $ids): array
    {
        return array_map(fn (string $id) => self::$cachedActivities[$id], $ids);
    }
}
