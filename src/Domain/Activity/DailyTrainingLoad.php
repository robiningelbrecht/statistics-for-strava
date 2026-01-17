<?php

namespace App\Domain\Activity;

use App\Domain\Athlete\AthleteRepository;
use App\Domain\Ftp\FtpHistory;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class DailyTrainingLoad
{
    /** @var array<string, int|null> */
    public static array $cachedLoad = [];

    public function __construct(
        private readonly EnrichedActivities $enrichedActivities,
        private readonly ActivityIntensity $activityIntensity,
        private readonly FtpHistory $ftpHistory,
        private readonly AthleteRepository $athleteRepository,
    ) {
    }

    public function calculate(SerializableDateTime $on): int
    {
        $cacheKey = $on->format('Y-m-d');
        if (array_key_exists($cacheKey, self::$cachedLoad) && null !== self::$cachedLoad[$cacheKey]) {
            return self::$cachedLoad[$cacheKey];
        }

        $activities = $this->enrichedActivities->findByStartDate(
            startDate: $on,
            activityType: null
        );
        $load = 0;

        /** @var Activity $activity */
        foreach ($activities as $activity) {
            $movingTimeInSeconds = $activity->getMovingTimeInSeconds();
            if (ActivityType::RIDE === $activity->getSportType()->getActivityType() && ($normalizedPower = $activity->getNormalizedPower())) {
                try {
                    $intensity = $this->activityIntensity->calculatePowerBased($activity->getId());
                    $intensity /= 100;
                    $ftp = $this->ftpHistory->find(ActivityType::RIDE, $activity->getStartDate())->getFtp();
                    $load += ($movingTimeInSeconds * $normalizedPower * $intensity) / ($ftp->getValue() * 3600) * 100;

                    continue;
                } catch (CouldNotDetermineActivityIntensity|EntityNotFound) {
                }
            }

            try {
                $intensity = $this->activityIntensity->calculateHeartRateBased($activity->getId());
                $intensity /= 100;
                $bannisterKFactor = 'M' === $this->athleteRepository->find()->getSex() ? 1.92 : 1.67;
                $load += Seconds::from($movingTimeInSeconds)->toMinute()->toFloat() * $intensity * exp($bannisterKFactor * $intensity);
            } catch (CouldNotDetermineActivityIntensity) {
            }
        }

        self::$cachedLoad[$cacheKey] = (int) round($load);

        return self::$cachedLoad[$cacheKey];
    }
}
