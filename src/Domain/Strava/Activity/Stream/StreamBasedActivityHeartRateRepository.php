<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Athlete\HeartRateZone\HeartRateZone;
use App\Domain\Strava\Athlete\HeartRateZone\HeartRateZoneConfiguration;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Time\Clock\Clock;

final class StreamBasedActivityHeartRateRepository implements ActivityHeartRateRepository
{
    private const int CALCULATE_HEART_RATE_ZONES_FOR_LAST_X_DAYS = 30;

    /** @var array<string, int> */
    public static array $cachedHeartRateZones = [];
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

    public function findTotalTimeInSecondsInHeartRateZone(string $heartRateZoneName): int
    {
        $this->buildHeartRateZoneCache();

        return StreamBasedActivityHeartRateRepository::$cachedHeartRateZones[$heartRateZoneName];
    }

    public function findTotalTimeInSecondsInHeartRateZoneForLast30Days(string $heartRateZoneName): int
    {
        $this->buildHeartRateZoneCache();

        return StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesInLastXDays[$heartRateZoneName];
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
        StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesInLastXDays = StreamBasedActivityHeartRateRepository::$cachedHeartRateZones;

        /** @var \App\Domain\Strava\Activity\Activity $activity */
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

                StreamBasedActivityHeartRateRepository::$cachedHeartRateZones[$heartRateZone->getName()] += $secondsInZone;

                if ($activityStartDate->isAfterOrOn($cutOffDate)) {
                    // Cache heart rate zones for the last X days
                    StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesInLastXDays[$heartRateZone->getName()] += $secondsInZone;
                }
            }
        }
    }
}
