<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\WeeklyDistanceTimeChart;
use App\Domain\Calendar\Weeks;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class WeeklyStatsWidget implements Widget
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
        private UnitSystem $unitSystem,
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

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $weeklyDistanceTimeCharts = $weeksPerActivityType = [];
        $activitiesPerActivityType = $this->activitiesEnricher->getActivitiesPerActivityType();

        foreach ($activitiesPerActivityType as $activityType => $activities) {
            if ($activities->isEmpty()) {
                continue;
            }

            $activityType = ActivityType::from($activityType);
            $weeks = Weeks::create(
                startDate: $activities->getFirstActivityStartDate(),
                now: $now
            );

            $weeksPerActivityType[$activityType->value] = Json::encode([
                'weeks' => $weeks,
                'sportTypes' => $activityType->getSportTypes(),
            ]);

            if ($activityType->supportsWeeklyStats() && $chartData = WeeklyDistanceTimeChart::create(
                activities: $activitiesPerActivityType[$activityType->value],
                unitSystem: $this->unitSystem,
                activityType: $activityType,
                now: $now,
                translator: $this->translator,
            )->build()) {
                $weeklyDistanceTimeCharts[$activityType->value] = Json::encode($chartData);
            }
        }

        return $this->twig->load('html/dashboard/widget/widget--weekly-stats.html.twig')->render([
            'weeklyDistanceCharts' => $weeklyDistanceTimeCharts,
            'weeksPerActivityType' => $weeksPerActivityType,
        ]);
    }
}
