<?php

declare(strict_types=1);

namespace App\Domain\Activity\BestEffort;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\ActivityTypeRepository;
use App\Domain\Activity\ActivityTypes;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypeRepository;
use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\ValueObject\Measurement\Length\ConvertableToMeter;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class ActivityBestEffortsCalculator
{
    /** @var array<string, ActivityBestEffort> */
    private array $cachedBestEfforts = [];

    /** @var array<string, array<string, array<int, string>>> */
    private array $cache = [];
    /** @var array<string, string[]> */
    private array $cachedPerActivity = [];
    private ActivityTypes $cachedActivityTypes;

    public function __construct(
        private readonly Connection $connection,
        private readonly SportTypeRepository $sportTypeRepository,
        private readonly ActivityTypeRepository $activityTypeRepository,
        private readonly Clock $clock,
    ) {
    }

    private function buildCache(): void
    {
        if (!empty($this->cachedBestEfforts)) {
            return;
        }

        $this->cachedActivityTypes = ActivityTypes::empty();
        foreach (BestEffortPeriod::cases() as $period) {
            $sql = 'SELECT activityId, sportType, distanceInMeter, timeInSeconds
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

            $dateRange = $period->getDateRange($this->clock->getCurrentDateTimeImmutable());
            $results = $this->connection->executeQuery(
                $sql,
                [
                    'sportTypes' => array_map(fn (SportType $sportType) => $sportType->value, SportTypes::thatSupportsBestEfforts()->toArray()),
                    'dateFrom' => $dateRange->getFrom()->format('Y-m-d 00:00:00'),
                    'dateTo' => $dateRange->getTill()->format('Y-m-d 23:59:59'),
                ],
                [
                    'sportTypes' => ArrayParameterType::STRING,
                ]
            )->fetchAllAssociative();

            foreach ($results as $result) {
                $activityId = ActivityId::fromString($result['activityId']);
                $sportType = SportType::from($result['sportType']);
                $distance = Meter::from($result['distanceInMeter']);
                $activityBestEffort = ActivityBestEffort::fromState(
                    activityId: $activityId,
                    distanceInMeter: $distance,
                    sportType: $sportType,
                    timeInSeconds: $result['timeInSeconds']
                );

                $this->cachedBestEfforts[$activityBestEffort->getId()] = $activityBestEffort;
                $this->cache[$period->value][$sportType->value][$distance->toInt()] = $activityBestEffort->getId();
                $this->cachedPerActivity[(string) $activityId][] = $activityBestEffort->getId();

                if (!$this->cachedActivityTypes->has($sportType->getActivityType())) {
                    $this->cachedActivityTypes->add($sportType->getActivityType());
                }
            }
        }
    }

    public function for(BestEffortPeriod $period, SportType $sportType, ConvertableToMeter $distance): ?ActivityBestEffort
    {
        $this->buildCache();

        $distance = $distance->toMeter()->toInt();
        $id = $this->cache[$period->value][$sportType->value][$distance] ?? 'unexisting';

        return $this->cachedBestEfforts[$id] ?? null;
    }

    public function forActivity(ActivityId $activityId): ActivityBestEfforts
    {
        $this->buildCache();

        $ids = $this->cachedPerActivity[(string) $activityId] ?? [];

        return ActivityBestEfforts::fromArray(array_map(fn (string $id) => $this->cachedBestEfforts[$id], $ids));
    }

    /**
     * @return BestEffortPeriod[]
     */
    public function getPeriods(): array
    {
        $this->buildCache();

        $periods = array_keys($this->cache);

        return array_map(
            BestEffortPeriod::from(...),
            $periods,
        );
    }

    public function getSportTypesFor(BestEffortPeriod $period, ActivityType $activityType): SportTypes
    {
        $this->buildCache();

        $sportTypes = SportTypes::empty();
        $importedSportTypes = $this->sportTypeRepository->findAll();

        foreach ($importedSportTypes as $sportType) {
            if ($sportType->getActivityType() !== $activityType) {
                continue;
            }
            if (empty($this->cache[$period->value][$sportType->value])) {
                continue;
            }
            $sportTypes->add($sportType);
        }

        return $sportTypes;
    }

    public function getActivityTypes(): ActivityTypes
    {
        $this->buildCache();

        $activityTypes = ActivityTypes::empty();

        $importedActivityTypes = $this->activityTypeRepository->findAll();
        foreach ($importedActivityTypes as $activityType) {
            if (!$this->cachedActivityTypes->has($activityType)) {
                continue;
            }
            $activityTypes->add($activityType);
        }

        return $activityTypes;
    }
}
