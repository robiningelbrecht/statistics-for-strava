<?php

namespace App\Domain\Activity;

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
use Doctrine\DBAL\Connection;

final class EnrichedActivities
{
    /** @var array<string, Activity> */
    private static array $cachedActivities = [];
    /** @var array<string, string[]> */
    private static array $cachedActivitiesPerActivityType = [];

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

    public static function reset(): void
    {
        self::$cachedActivities = [];
        self::$cachedActivitiesPerActivityType = [];
    }

    private function enrichAll(): void
    {
        if ([] !== self::$cachedActivities) {
            return;
        }

        $tags = [
            ...$this->gearMaintenanceConfig->getAllMaintenanceTags(),
            ...$this->customGearConfig->getAllGearTags(),
        ];
        $gears = $this->gearRepository->findAll();
        $normalizedPowers = $this->fetchNormalizedPowers();
        $maxCadences = $this->fetchMaxCadences();

        $activityTypes = $this->activityTypeRepository->findAll();
        foreach ($activityTypes as $activityType) {
            self::$cachedActivitiesPerActivityType[$activityType->value] = [];
        }

        $activities = $this->activityRepository->findAll();

        foreach ($activities as $activity) {
            $activityId = (string) $activity->getId();
            $activity = $activity
                ->withNormalizedPower($normalizedPowers[$activityId] ?? null)
                ->withBestPowerOutputs(
                    $this->activityPowerRepository->findBest($activity->getId())
                )
                ->withGearName(
                    $gears->getByGearId($activity->getGearId())?->getName()
                )
                ->withTags($tags);

            if (isset($maxCadences[$activityId])) {
                $activity = $activity->withMaxCadence($maxCadences[$activityId]);
            }

            self::$cachedActivities[$activityId] = $activity;
            self::$cachedActivitiesPerActivityType[$activity->getSportType()->getActivityType()->value][] = $activityId;
        }
    }

    /**
     * @return array<string, int|null>
     */
    private function fetchNormalizedPowers(): array
    {
        $results = $this->connection->executeQuery(
            'SELECT activityId, data FROM ActivityStreamMetric
             WHERE streamType = :streamType AND metricType = :metricType',
            [
                'streamType' => StreamType::WATTS->value,
                'metricType' => ActivityStreamMetricType::NORMALIZED_POWER->value,
            ]
        )->fetchAllAssociative();

        $normalizedPowers = [];
        foreach ($results as $result) {
            $decoded = Json::uncompressAndDecode($result['data']);
            $normalizedPowers[$result['activityId']] = $decoded[0] ?? null;
        }

        return $normalizedPowers;
    }

    /**
     * @return array<string, int>
     */
    private function fetchMaxCadences(): array
    {
        $cadenceStreams = $this->activityStreamRepository->findByStreamType(StreamType::CADENCE);

        $maxCadences = [];
        foreach ($cadenceStreams as $stream) {
            $data = $stream->getData();
            if ([] !== $data) {
                $maxCadences[(string) $stream->getActivityId()] = max($data);
            }
        }

        return $maxCadences;
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

        $dateString = $startDate->format('Y-m-d');
        $allowedSportTypes = $activityType?->getSportTypes();

        $filtered = array_filter(self::$cachedActivities, function (Activity $activity) use ($dateString, $allowedSportTypes): bool {
            if ($activity->getStartDate()->format('Y-m-d') !== $dateString) {
                return false;
            }

            if ($allowedSportTypes instanceof SportTypes) {
                return in_array($activity->getSportType(), $allowedSportTypes->toArray(), true);
            }

            return true;
        });

        return Activities::fromArray($filtered);
    }

    public function findBySportTypes(SportTypes $sportTypes): Activities
    {
        $this->enrichAll();

        $sportTypeValues = $sportTypes->toArray();

        return Activities::fromArray(
            array_filter(
                self::$cachedActivities,
                fn (Activity $activity): bool => in_array($activity->getSportType(), $sportTypeValues, true),
            )
        );
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
