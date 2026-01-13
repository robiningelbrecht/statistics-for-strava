<?php

namespace App\Domain\Dashboard\Widget\AthleteProfile;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Activity\ActivityIntensity;
use App\Domain\Dashboard\Widget\AthleteProfile\FindAthleteProfileMetrics\FindAthleteProfileMetrics;
use App\Domain\Dashboard\Widget\Widget;
use App\Domain\Dashboard\Widget\WidgetConfiguration;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Time\DateRange;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class AthleteProfileWidget implements Widget
{
    public function __construct(
        private QueryBus $queryBus,
        private ActivitiesEnricher $activitiesEnricher,
        private ActivityIntensity $activityIntensity,
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty();
    }

    public function guardValidConfiguration(WidgetConfiguration $configuration): void
    {
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): ?string
    {
        foreach ([30, 90] as $lastXDays) {
            $findAthleteProfileMetricsResponse = $this->queryBus->ask(new FindAthleteProfileMetrics(DateRange::lastXDays($now, $lastXDays)));
            $numberOfActivities = $findAthleteProfileMetricsResponse->getNumberOfActivities();
            if (0 === $numberOfActivities) {
                continue;
            }

            $movingTimeInHours = $findAthleteProfileMetricsResponse->getMovingTime();
            $numberOfActiveDays = $findAthleteProfileMetricsResponse->getNumberOfActiveDays();

            // VOLUME: 10 h/week = very active amateur (works across sports).
            $weeklyHours = $movingTimeInHours->toFloat() / ($lastXDays / 7);
            $volume = min(100, $weeklyHours / 10 * 100);

            // CONSISTENCY: 5 days/week = excellent consistency.
            $activeDaysRatio = $numberOfActiveDays / $lastXDays;
            $consistency = min(100, $activeDaysRatio / 0.7 * 100);

            // INTENSITY: 25% = realistic upper bound for sustainable hard training.
            $countActivitiesWithHighEffort = 0;
            foreach ($findAthleteProfileMetricsResponse->getActivityIds() as $activityId) {
                $activityIntensity = $this->activityIntensity->calculateForActivity($this->activitiesEnricher->getEnrichedActivity($activityId));
                if (ActivityIntensity::ACTIVITY_HIGH_THRESHOLD_VALUE > $activityIntensity) {
                    continue;
                }
                ++$countActivitiesWithHighEffort;
            }
            $intensity = min(100, ($countActivitiesWithHighEffort / $numberOfActivities) / 0.25 * 100);

            // DURATION: Median > 90 min = endurance-leaning athlete.
            $activityMovingTimes = $findAthleteProfileMetricsResponse->getActivityMovingTimesInSeconds();
            sort($activityMovingTimes);

            $middleIndex = (int) floor(count($activityMovingTimes) / 2);
            sort($activityMovingTimes, SORT_NUMERIC);
            $medianInSeconds = $activityMovingTimes[$middleIndex];
            // Handle the even case by averaging the middle 2 items.
            if (0 == count($activityMovingTimes) % 2) {
                $medianInSeconds = ($medianInSeconds + $activityMovingTimes[$middleIndex - 1]) / 2;
            }
            $duration = min(100, Seconds::from($medianInSeconds)->toMinute()->toFloat() / 90 * 100);

            // DENSITY: 2h per training day = high density.
            $averageTrainingDurationPerDay = $movingTimeInHours->toFloat() / $numberOfActiveDays;
            $density = min(100, $averageTrainingDurationPerDay / 2 * 100);

            // VARIETY: 0.5 is the anchor that defines "max meaningful variety"
            $dominantSportFraction = $findAthleteProfileMetricsResponse->getNumberOfActivitiesInMostPopularActivityType() / $numberOfActivities;
            $variety = min(100, (1 - $dominantSportFraction) / 0.5 * 100);

            $chartData[$lastXDays] = [
                round($volume),
                round($consistency),
                round($intensity),
                round($duration),
                round($density),
                round($variety),
            ];
        }

        if (empty($chartData)) {
            return null;
        }

        return $this->twig->load('html/dashboard/widget/widget--athlete-profile.html.twig')->render([
            'athleteProfileChart' => Json::encode(
                AthleteProfileChart::create(
                    chartData: $chartData,
                    translator: $this->translator
                )->build()
            ),
        ]);
    }
}
