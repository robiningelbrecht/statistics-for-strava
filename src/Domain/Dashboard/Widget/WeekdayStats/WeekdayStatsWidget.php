<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\WeekdayStats;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Dashboard\Widget\Widget;
use App\Domain\Dashboard\Widget\WidgetConfiguration;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class WeekdayStatsWidget implements Widget
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
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
        $statsPerActivityType = [];
        $activitiesPerActivityType = $this->activitiesEnricher->getActivitiesPerActivityType();
        if (count($activitiesPerActivityType) > 1) {
            foreach ($activitiesPerActivityType as $activityType => $activities) {
                $weekdayStats = WeekdayStats::create(
                    activities: $activities,
                    translator: $this->translator,
                );
                $statsPerActivityType[$activityType] = [
                    'chart' => Json::encode(
                        WeekdayStatsChart::create($weekdayStats)->build(),
                    ),
                    'weekDayStats' => $weekdayStats,
                ];
            }
        }

        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $allWeekdayStats = WeekdayStats::create(
            activities: $allActivities,
            translator: $this->translator,
        );

        return $this->twig->load('html/dashboard/widget/widget--weekday-stats.html.twig')->render([
            'allActivities' => [
                'chart' => Json::encode(
                    WeekdayStatsChart::create($allWeekdayStats)->build(),
                ),
                'weekDayStats' => $allWeekdayStats,
            ],
            'statsPerActivityType' => $statsPerActivityType,
        ]);
    }
}
