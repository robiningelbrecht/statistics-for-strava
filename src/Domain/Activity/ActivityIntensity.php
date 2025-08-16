<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Domain\Athlete\AthleteRepository;
use App\Domain\Ftp\FtpHistory;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ActivityIntensity
{
    /** @var array<string, int|null> */
    public static array $cachedIntensities = [];

    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly AthleteRepository $athleteRepository,
        private readonly FtpHistory $ftpHistory,
    ) {
    }

    public function calculateForDate(SerializableDateTime $on): int
    {
        $cacheKey = $on->format('Y-m-d');
        if (array_key_exists($cacheKey, self::$cachedIntensities) && null !== self::$cachedIntensities[$cacheKey]) {
            return self::$cachedIntensities[$cacheKey];
        }

        $activities = $this->activityRepository->findByStartDate(
            startDate: $on,
            activityType: null
        );
        self::$cachedIntensities[$cacheKey] = 0;

        /** @var Activity $activity */
        foreach ($activities as $activity) {
            if (!$intensity = $this->calculateForActivity($activity)) {
                continue;
            }

            self::$cachedIntensities[$cacheKey] += $intensity;
        }

        return self::$cachedIntensities[$cacheKey];
    }

    private function calculateForActivity(Activity $activity): ?int
    {
        $athlete = $this->athleteRepository->find();
        try {
            // To calculate intensity, we need
            // 1) Max and average heart rate
            // OR
            // 2) FTP and average power
            $ftp = $this->ftpHistory->find($activity->getStartDate())->getFtp();
            if ($averagePower = $activity->getAveragePower()) {
                // Use more complicated and more accurate calculation.
                // intensityFactor = averagePower / FTP
                // (durationInSeconds * averagePower * intensityFactor) / (FTP x 3600) * 100
                return (int) round(($activity->getMovingTimeInSeconds() * $averagePower * ($averagePower / $ftp->getValue())) / ($ftp->getValue() * 3600) * 100);
            }
        } catch (EntityNotFound) {
        }

        if ($averageHeartRate = $activity->getAverageHeartRate()) {
            $athleteMaxHeartRate = $athlete->getMaxHeartRate($activity->getStartDate());
            // Use simplified, less accurate calculation.
            // maxHeartRate = = (220 - age) x 0.92
            // intensityFactor = averageHeartRate / maxHeartRate
            // (durationInSeconds x averageHeartRate x intensityFactor) / (maxHeartRate x 3600) x 100
            $maxHeartRate = round($athleteMaxHeartRate * 0.92);

            return (int) round(($activity->getMovingTimeInSeconds() * $averageHeartRate * ($averageHeartRate / $maxHeartRate)) / ($maxHeartRate * 3600) * 100);
        }

        return null;
    }
}
