<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Athlete\HeartRateZone\HeartRateZone;
use App\Domain\Strava\Athlete\HeartRateZone\HeartRateZoneConfiguration;

final class StreamBasedActivityHeartRateRepository implements ActivityHeartRateRepository
{
    /** @var array<mixed> */
    public static array $cachedHeartRateZonesPerActivity = [];

    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityStreamRepository $activityStreamRepository,
        private readonly AthleteRepository $athleteRepository,
        private readonly HeartRateZoneConfiguration $heartRateZoneConfiguration,
    ) {
    }

    public function findTotalTimeInSecondsInHeartRateZone(string $heartRateZoneName): int
    {
        $cachedHeartRateZones = $this->getCachedHeartRateZones();

        return array_sum(array_map(fn (array $heartRateZones) => $heartRateZones[$heartRateZoneName], $cachedHeartRateZones));
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

    /**
     * @return array<string, mixed>
     */
    private function getCachedHeartRateZones(): array
    {
        if (!empty(StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivity)) {
            return StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivity;
        }

        $athlete = $this->athleteRepository->find();
        $activities = $this->activityRepository->findAll();
        $heartRateStreams = $this->activityStreamRepository->findByStreamType(StreamType::HEART_RATE);

        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($activities as $activity) {
            StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivity[(string) $activity->getId()] = [
                HeartRateZone::ONE => 0,
                HeartRateZone::TWO => 0,
                HeartRateZone::THREE => 0,
                HeartRateZone::FOUR => 0,
                HeartRateZone::FIVE => 0,
            ];
            $heartRateStreamsForActivity = $heartRateStreams->filter(fn (ActivityStream $stream) => $stream->getActivityId() == $activity->getId());

            if ($heartRateStreamsForActivity->isEmpty()) {
                continue;
            }

            $athleteMaxHeartRate = $athlete->getMaxHeartRate($activity->getStartDate());

            /** @var ActivityStream $stream */
            $stream = $heartRateStreamsForActivity->getFirst();
            $athleteHeartRateZones = $this->heartRateZoneConfiguration->getHeartRateZonesFor(
                sportType: $activity->getSportType(),
                on: $activity->getStartDate()
            );

            foreach ($athleteHeartRateZones->getZones() as $heartRateZone) {
                [$minHeartRate, $maxHeartRate] = $heartRateZone->getRangeInBpm($athleteMaxHeartRate);
                $secondsInZone = count(array_filter($stream->getData(), fn (int $heartRate) => $heartRate >= $minHeartRate && $heartRate <= $maxHeartRate));
                StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivity[(string) $activity->getId()][$heartRateZone->getName()] = $secondsInZone;
            }
        }

        return StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivity;
    }
}
