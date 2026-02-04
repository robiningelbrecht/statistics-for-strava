<?php

namespace App\Domain\Activity\Stream;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Activity\ActivitySummaryRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Athlete\Weight\AthleteWeightHistory;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\DateRange;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Carbon\CarbonInterval;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class StreamBasedActivityPowerRepository implements ActivityPowerRepository
{
    /** @var array<string, PowerOutputs> */
    public static array $cachedPowerOutputs = [];
    /** @var array<string, ?int> */
    public static array $cachedNormalizedPowers = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly ActivityIdRepository $activityIdRepository,
        private readonly ActivitySummaryRepository $activitySummaryRepository,
        private readonly AthleteWeightHistory $athleteWeightHistory,
        private readonly ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function findNormalizedPower(ActivityId $activityId): ?int
    {
        $this->buildStaticCaches();

        return StreamBasedActivityPowerRepository::$cachedNormalizedPowers[(string) $activityId];
    }

    public function findBest(ActivityId $activityId): PowerOutputs
    {
        $this->buildStaticCaches();

        return StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activityId];
    }

    private function buildStaticCaches(): void
    {
        if ([] !== StreamBasedActivityPowerRepository::$cachedPowerOutputs) {
            return;
        }

        $activityIds = $this->activityIdRepository->findAll();
        foreach ($activityIds as $activityId) {
            StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activityId] = PowerOutputs::empty();
            StreamBasedActivityPowerRepository::$cachedNormalizedPowers[(string) $activityId] = null;

            try {
                $powerStreamForActivity = $this->activityStreamRepository->findOneByActivityAndStreamType(
                    activityId: $activityId,
                    streamType: StreamType::WATTS
                );
                StreamBasedActivityPowerRepository::$cachedNormalizedPowers[(string) $activityId] = $powerStreamForActivity->getNormalizedPower();
            } catch (EntityNotFound) {
                continue;
            }

            $bestAverages = $powerStreamForActivity->getBestAverages();

            foreach (self::TIME_INTERVALS_IN_SECONDS_REDACTED as $timeIntervalInSeconds) {
                $interval = CarbonInterval::seconds($timeIntervalInSeconds);
                if (!isset($bestAverages[$timeIntervalInSeconds])) {
                    continue;
                }
                $bestAverageForTimeInterval = $bestAverages[$timeIntervalInSeconds];

                $activitySummary = $this->activitySummaryRepository->find($activityId);
                try {
                    $athleteWeight = $this->athleteWeightHistory->find($activitySummary->getStartDate())->getWeightInKg();
                } catch (EntityNotFound) {
                    throw new EntityNotFound(sprintf('Trying to calculate the relative power for activity "%s" on %s, but no corresponding athleteWeight was found. 
                    Make sure you configure the proper weights in your config.yaml file. Do not forgot to restart your container after changing the weights', $activitySummary->getName(), $activitySummary->getStartDate()->format('Y-m-d')));
                }

                $relativePower = $athleteWeight->toFloat() > 0 ? round($bestAverageForTimeInterval / $athleteWeight->toFloat(), 2) : 0;
                StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activityId]->add(PowerOutput::fromState(
                    timeIntervalInSeconds: $timeIntervalInSeconds,
                    formattedTimeInterval: 0 !== (int) $interval->totalHours ? $interval->totalHours.' h' : (0 !== (int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s'),
                    power: $bestAverageForTimeInterval,
                    relativePower: $relativePower,
                ));
            }
        }
    }

    public function findBestForSportTypes(SportTypes $sportTypes): PowerOutputs
    {
        return $this->buildBestFor(
            sportTypes: $sportTypes,
            dateRange: null
        );
    }

    public function findBestForSportTypesInDateRange(SportTypes $sportTypes, DateRange $dateRange): PowerOutputs
    {
        return $this->buildBestFor(
            sportTypes: $sportTypes,
            dateRange: $dateRange
        );
    }

    private function buildBestFor(SportTypes $sportTypes, ?DateRange $dateRange): PowerOutputs
    {
        $powerOutputs = PowerOutputs::empty();

        if (!$dateRange instanceof DateRange) {
            $dateRange = DateRange::fromDates(
                from: SerializableDateTime::fromString('1970-01-01 00:00:00'),
                till: SerializableDateTime::fromString('2100-01-01 00:00:00')
            );
        }

        foreach (self::TIME_INTERVALS_IN_SECONDS_ALL as $timeIntervalInSeconds) {
            $query = 'SELECT ActivityStream.activityId, ActivityStream.bestAverages FROM ActivityStream 
                        INNER JOIN Activity ON Activity.activityId = ActivityStream.activityId 
                        WHERE streamType = :streamType
                        AND Activity.sportType IN(:sportType)
                        AND Activity.startDateTime >= :dateFrom AND Activity.startDateTime <= :dateTill  
                        AND JSON_EXTRACT(bestAverages, "$.'.$timeIntervalInSeconds.'") IS NOT NULL
                        ORDER BY JSON_EXTRACT(bestAverages, "$.'.$timeIntervalInSeconds.'") DESC, createdOn DESC LIMIT 1';

            if (!$result = $this->connection->executeQuery(
                $query,
                [
                    'streamType' => StreamType::WATTS->value,
                    'sportType' => $sportTypes->map(fn (SportType $sportType) => $sportType->value),
                    'dateFrom' => $dateRange->getFrom()->format('Y-m-d 00:00:00'),
                    'dateTill' => $dateRange->getTill()->format('Y-m-d 23:59:59'),
                ],
                [
                    'sportType' => ArrayParameterType::STRING,
                ]
            )->fetchAssociative()) {
                continue;
            }

            $activityId = ActivityId::fromString($result['activityId']);
            $activitySummary = $this->activitySummaryRepository->find($activityId);
            $interval = CarbonInterval::seconds($timeIntervalInSeconds);
            $bestAverageForTimeInterval = Json::decode($result['bestAverages'])[$timeIntervalInSeconds];

            try {
                $athleteWeight = $this->athleteWeightHistory->find($activitySummary->getStartDate())->getWeightInKg();
            } catch (EntityNotFound) {
                throw new EntityNotFound(sprintf('Trying to calculate the relative power for activity "%s" on %s, but no corresponding athleteWeight was found. 
                    Make sure you configure the proper weights in your config.yaml file. Do not forgot to restart your container after changing the weights', $activitySummary->getName(), $activitySummary->getStartDate()->format('Y-m-d')));
            }

            $relativePower = $athleteWeight->toFloat() > 0 ? round($bestAverageForTimeInterval / $athleteWeight->toFloat(), 2) : 0;
            $powerOutputs->add(
                PowerOutput::fromState(
                    timeIntervalInSeconds: $timeIntervalInSeconds,
                    formattedTimeInterval: 0 !== (int) $interval->totalHours ? $interval->totalHours.' h' : (0 !== (int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s'),
                    power: $bestAverageForTimeInterval,
                    relativePower: $relativePower,
                    activityId: $activityId,
                )
            );
        }

        return $powerOutputs;
    }
}
