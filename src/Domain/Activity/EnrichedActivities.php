<?php

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Activity\Stream\ActivityPowerRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Domain\Gear\CustomGear\CustomGearConfig;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\Maintenance\GearMaintenanceConfig;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class EnrichedActivities
{
    /** @var array<string, Activity> */
    public static array $cachedActivities = [];
    /** @var array<string, Activities> */
    public static array $cachedActivitiesPerActivityType;

    public function __construct(
        private readonly Connection $connection,
        private readonly ActivityRepository $activityRepository,
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
        $queryBuilder->select('activityId')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC');

        $results = $queryBuilder->executeQuery()->fetchFirstColumn();

        foreach ($results as $result) {
            $activity = $this->activityRepository->find(ActivityId::fromString($result));
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

    /**
     * @return array<string, Activities>
     */
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
}
