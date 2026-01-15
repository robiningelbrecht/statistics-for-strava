<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Domain\Athlete\AthleteRepository;
use App\Domain\Ftp\FtpHistory;
use App\Infrastructure\Exception\EntityNotFound;

final class ActivityIntensity
{
    public const int HIGH_INTENSITY_THRESHOLD_VALUE = 88;

    /** @var array<string, int|null> */
    public static array $cachedIntensities = [];

    public function __construct(
        private readonly ActivitiesEnricher $activitiesEnricher,
        private readonly AthleteRepository $athleteRepository,
        private readonly FtpHistory $ftpHistory,
    ) {
    }

    public function calculate(Activity $activity): int
    {
        $cacheKey = (string) $activity->getId();
        if (array_key_exists($cacheKey, self::$cachedIntensities) && null !== self::$cachedIntensities[$cacheKey]) {
            return self::$cachedIntensities[$cacheKey];
        }

        try {
            return $this->calculatePowerBased($activity);
        } catch (CouldNotDetermineActivityIntensity) {
        }

        try {
            return $this->calculateHeartRateBased($activity);
        } catch (CouldNotDetermineActivityIntensity) {
        }

        self::$cachedIntensities[$cacheKey] = 0;

        return 0;
    }

    public function calculatePowerBased(Activity $activity): int
    {
        if (ActivityType::RIDE !== $activity->getSportType()->getActivityType()) {
            throw new CouldNotDetermineActivityIntensity();
        }

        $cacheKey = (string) $activity->getId();
        if (array_key_exists($cacheKey, self::$cachedIntensities) && null !== self::$cachedIntensities[$cacheKey]) {
            return self::$cachedIntensities[$cacheKey];
        }

        $activity = $this->activitiesEnricher->getEnrichedActivity($activity->getId());
        if (!$normalizedPower = $activity->getNormalizedPower()) {
            throw new CouldNotDetermineActivityIntensity();
        }

        try {
            $ftp = $this->ftpHistory->find(ActivityType::RIDE, $activity->getStartDate())->getFtp();
            // IF = Normalized Power / FTP
            $intensity = (int) round(($normalizedPower / $ftp->getValue()) * 100);
            self::$cachedIntensities[$cacheKey] = $intensity;

            return self::$cachedIntensities[$cacheKey];
        } catch (EntityNotFound) {
        }

        throw new CouldNotDetermineActivityIntensity();
    }

    public function calculateHeartRateBased(Activity $activity): int
    {
        $athlete = $this->athleteRepository->find();
        $cacheKey = (string) $activity->getId();

        if (!$averageHeartRate = $activity->getAverageHeartRate()) {
            throw new CouldNotDetermineActivityIntensity();
        }

        $athleteRestingHeartRate = $athlete->getRestingHeartRateFormula($activity->getStartDate());
        $athleteMaxHeartRate = $athlete->getMaxHeartRate($activity->getStartDate());

        $intensity = (int) round(($averageHeartRate - $athleteRestingHeartRate) / ($athleteMaxHeartRate - $athleteRestingHeartRate) * 100);
        self::$cachedIntensities[$cacheKey] = $intensity;

        return self::$cachedIntensities[$cacheKey];
    }
}
