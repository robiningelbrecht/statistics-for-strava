<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityType;
use App\Domain\Athlete\AthleteRepository;
use App\Domain\Athlete\HeartRateZone\HeartRateZone;
use App\Domain\Athlete\HeartRateZone\HeartRateZoneConfiguration;
use App\Domain\Athlete\HeartRateZone\TimeInHeartRateZones;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Time\Clock\Clock;

final class StreamBasedActivityHeartRateRepository implements ActivityHeartRateRepository
{
    private const int CALCULATE_HEART_RATE_ZONES_FOR_LAST_X_DAYS = 30;

    /** @var array<string, int> */
    public static array $cachedHeartRateZones = [];
    /** @var array<string, array<string, int>> */
    public static array $cachedHeartRateZonesPerActivityType = [];
    /** @var array<string, int> */
    public static array $cachedHeartRateZonesInLastXDays = [];

    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityStreamRepository $activityStreamRepository,
        private readonly AthleteRepository $athleteRepository,
        private readonly HeartRateZoneConfiguration $heartRateZoneConfiguration,
        private readonly Clock $clock,
    ) {
    }

    public function findTotalTimeInSecondsInHeartRateZones(): TimeInHeartRateZones
    {
        $this->buildHeartRateZoneCache();

        return TimeInHeartRateZones::create(
            timeInZoneOne: StreamBasedActivityHeartRateRepository::$cachedHeartRateZones[HeartRateZone::ONE],
            timeInZoneTwo: StreamBasedActivityHeartRateRepository::$cachedHeartRateZones[HeartRateZone::TWO],
            timeInZoneThree: StreamBasedActivityHeartRateRepository::$cachedHeartRateZones[HeartRateZone::THREE],
            timeInZoneFour: StreamBasedActivityHeartRateRepository::$cachedHeartRateZones[HeartRateZone::FOUR],
            timeInZoneFive: StreamBasedActivityHeartRateRepository::$cachedHeartRateZones[HeartRateZone::FIVE],
        );
    }

    public function findTotalTimeInSecondsInHeartRateZonesForActivityType(ActivityType $activityType): TimeInHeartRateZones
    {
        $this->buildHeartRateZoneCache();

        return TimeInHeartRateZones::create(
            timeInZoneOne: StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivityType[$activityType->value][HeartRateZone::ONE],
            timeInZoneTwo: StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivityType[$activityType->value][HeartRateZone::TWO],
            timeInZoneThree: StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivityType[$activityType->value][HeartRateZone::THREE],
            timeInZoneFour: StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivityType[$activityType->value][HeartRateZone::FOUR],
            timeInZoneFive: StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivityType[$activityType->value][HeartRateZone::FIVE],
        );
    }

    public function findTotalTimeInSecondsInHeartRateZonesForLast30Days(): TimeInHeartRateZones
    {
        $this->buildHeartRateZoneCache();

        return TimeInHeartRateZones::create(
            timeInZoneOne: StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesInLastXDays[HeartRateZone::ONE],
            timeInZoneTwo: StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesInLastXDays[HeartRateZone::TWO],
            timeInZoneThree: StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesInLastXDays[HeartRateZone::THREE],
            timeInZoneFour: StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesInLastXDays[HeartRateZone::FOUR],
            timeInZoneFive: StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesInLastXDays[HeartRateZone::FIVE],
        );
    }

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerHeartRateForActivity(ActivityId $activityId): array
    {
        if (!$this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: $activityId,
            streamType: StreamType::HEART_RATE
        )) {
            return [];
        }

        $stream = $this->activityStreamRepository->findOneByActivityAndStreamType(
            activityId: $activityId,
            streamType: StreamType::HEART_RATE
        );
        $data = $stream->getData();
        $heartRateStreamForActivity = array_count_values($data);
        ksort($heartRateStreamForActivity);

        return $heartRateStreamForActivity;
    }

    private function buildHeartRateZoneCache(): void
    {
        if (!empty(StreamBasedActivityHeartRateRepository::$cachedHeartRateZones)) {
            // Cache already built, no need to rebuild.
            return;
        }

        $interval = \DateInterval::createFromDateString(self::CALCULATE_HEART_RATE_ZONES_FOR_LAST_X_DAYS.' days');
        $cutOffDate = $this->clock->getCurrentDateTimeImmutable()->sub($interval);
        $athlete = $this->athleteRepository->find();
        $activities = $this->activityRepository->findAll();

        StreamBasedActivityHeartRateRepository::$cachedHeartRateZones = [
            HeartRateZone::ONE => 0,
            HeartRateZone::TWO => 0,
            HeartRateZone::THREE => 0,
            HeartRateZone::FOUR => 0,
            HeartRateZone::FIVE => 0,
        ];
        foreach (ActivityType::cases() as $activityType) {
            StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivityType[$activityType->value] = [
                HeartRateZone::ONE => 0,
                HeartRateZone::TWO => 0,
                HeartRateZone::THREE => 0,
                HeartRateZone::FOUR => 0,
                HeartRateZone::FIVE => 0,
            ];
        }
        StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesInLastXDays = StreamBasedActivityHeartRateRepository::$cachedHeartRateZones;

        /** @var \App\Domain\Activity\Activity $activity */
        foreach ($activities as $activity) {
            try {
                $heartRateStreamForActivity = $this->activityStreamRepository->findOneByActivityAndStreamType(
                    activityId: $activity->getId(),
                    streamType: StreamType::HEART_RATE
                );
            } catch (EntityNotFound) {
                continue;
            }

            $activityStartDate = $activity->getStartDate();
            $athleteMaxHeartRate = $athlete->getMaxHeartRate($activity->getStartDate());
            $athleteHeartRateZones = $this->heartRateZoneConfiguration->getHeartRateZonesFor(
                sportType: $activity->getSportType(),
                on: $activityStartDate
            );

            foreach ($athleteHeartRateZones->getZones() as $heartRateZone) {
                [$minHeartRate, $maxHeartRate] = $heartRateZone->getRangeInBpm($athleteMaxHeartRate);
                $secondsInZone = 0;
                foreach ($heartRateStreamForActivity->getData() as $heartRate) {
                    if ($heartRate >= $minHeartRate && $heartRate <= $maxHeartRate) {
                        ++$secondsInZone;
                    }
                }

                $activityType = $activity->getSportType()->getActivityType();

                StreamBasedActivityHeartRateRepository::$cachedHeartRateZones[$heartRateZone->getName()] += $secondsInZone;
                StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivityType[$activityType->value][$heartRateZone->getName()] += $secondsInZone;

                if ($activityStartDate->isAfterOrOn($cutOffDate)) {
                    // Cache heart rate zones for the last X days
                    StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesInLastXDays[$heartRateZone->getName()] += $secondsInZone;
                }
            }
        }
    }
}
