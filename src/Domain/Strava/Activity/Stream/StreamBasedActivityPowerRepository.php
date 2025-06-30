<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypes;
use App\Domain\Strava\Athlete\Weight\AthleteWeightHistory;
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
    private static array $cachedPowerOutputs = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly ActivityRepository $activityRepository,
        private readonly AthleteWeightHistory $athleteWeightHistory,
        private readonly ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function findBestForActivity(ActivityId $activityId): PowerOutputs
    {
        if (array_key_exists((string) $activityId, StreamBasedActivityPowerRepository::$cachedPowerOutputs)) {
            return StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activityId];
        }

        $activities = $this->activityRepository->findAll();
        /** @var Activity $activity */
        foreach ($activities as $activity) {
            StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activity->getId()] = PowerOutputs::empty();

            try {
                $powerStreamForActivity = $this->activityStreamRepository->findOneByActivityAndStreamType(
                    activityId: $activity->getId(),
                    streamType: StreamType::WATTS
                );
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

                try {
                    $athleteWeight = $this->athleteWeightHistory->find($activity->getStartDate())->getWeightInKg();
                } catch (EntityNotFound) {
                    throw new EntityNotFound(sprintf('Trying to calculate the relative power for activity "%s" on %s, but no corresponding athleteWeight was found. 
                    Make sure you configure the proper weights in your config.yaml file. Do not forgot to restart your container after changing the weights', $activity->getName(), $activity->getStartDate()->format('Y-m-d')));
                }

                $relativePower = $athleteWeight->toFloat() > 0 ? round($bestAverageForTimeInterval / $athleteWeight->toFloat(), 2) : 0;
                StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activity->getId()]->add(PowerOutput::fromState(
                    timeIntervalInSeconds: $timeIntervalInSeconds,
                    formattedTimeInterval: (int) $interval->totalHours ? $interval->totalHours.' h' : ((int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s'),
                    power: $bestAverageForTimeInterval,
                    relativePower: $relativePower,
                ));
            }
        }

        return StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activityId];
    }

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerWattageForActivity(ActivityId $activityId): array
    {
        if (!$this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: $activityId,
            streamType: StreamType::WATTS
        )) {
            return [];
        }

        $stream = $this->activityStreamRepository->findOneByActivityAndStreamType(
            activityId: $activityId,
            streamType: StreamType::WATTS
        );
        $powerStreamForActivity = array_count_values(array_filter($stream->getData(), fn (mixed $item) => !is_null($item)));
        ksort($powerStreamForActivity);

        return $powerStreamForActivity;
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

        if (!$dateRange) {
            $dateRange = DateRange::fromDates(
                from: SerializableDateTime::fromString('1970-01-01 00:00:00'),
                till: SerializableDateTime::fromString('2100-01-01 00:00:00')
            );
        }

        foreach (self::TIME_INTERVALS_IN_SECONDS_ALL as $timeIntervalInSeconds) {
            $query = 'SELECT ActivityStream.* FROM ActivityStream 
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

            $stream = ActivityStream::fromState(
                activityId: ActivityId::fromString($result['activityId']),
                streamType: StreamType::from($result['streamType']),
                streamData: Json::decode($result['data']),
                createdOn: SerializableDateTime::fromString($result['createdOn']),
                bestAverages: Json::decode($result['bestAverages'] ?? '[]'),
            );

            $activity = $this->activityRepository->find($stream->getActivityId());
            $interval = CarbonInterval::seconds($timeIntervalInSeconds);
            $bestAverageForTimeInterval = $stream->getBestAverages()[$timeIntervalInSeconds];

            try {
                $athleteWeight = $this->athleteWeightHistory->find($activity->getStartDate())->getWeightInKg();
            } catch (EntityNotFound) {
                throw new EntityNotFound(sprintf('Trying to calculate the relative power for activity "%s" on %s, but no corresponding athleteWeight was found. 
                    Make sure you configure the proper weights in your config.yaml file. Do not forgot to restart your container after changing the weights', $activity->getName(), $activity->getStartDate()->format('Y-m-d')));
            }

            $relativePower = $athleteWeight->toFloat() > 0 ? round($bestAverageForTimeInterval / $athleteWeight->toFloat(), 2) : 0;
            $powerOutputs->add(
                PowerOutput::fromState(
                    timeIntervalInSeconds: $timeIntervalInSeconds,
                    formattedTimeInterval: (int) $interval->totalHours ? $interval->totalHours.' h' : ((int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s'),
                    power: $bestAverageForTimeInterval,
                    relativePower: $relativePower,
                    activity: $activity,
                )
            );
        }

        return $powerOutputs;
    }
}
