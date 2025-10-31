<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\DaytimeStats;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Dashboard\Widget\Widget;
use App\Domain\Dashboard\Widget\WidgetConfiguration;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class DayTimeStatsWidget implements Widget
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
                $dayTimeStats = DaytimeStats::create($activities);
                $statsPerActivityType[$activityType] = [
                    'chart' => Json::encode(
                        DaytimeStatsCharts::create(
                            daytimeStats: $dayTimeStats,
                            translator: $this->translator,
                        )->build(),
                    ),
                    'dayTimeStats' => $dayTimeStats,
                ];
            }
        }

        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $allDayTimeStats = DaytimeStats::create($allActivities);

        return $this->twig->load('html/dashboard/widget/widget--day-time-stats.html.twig')->render([
            'allActivities' => [
                'chart' => Json::encode(
                    DaytimeStatsCharts::create(
                        daytimeStats: $allDayTimeStats,
                        translator: $this->translator,
                    )->build(),
                ),
                'dayTimeStats' => $allDayTimeStats,
            ],
            'statsPerActivityType' => $statsPerActivityType,
        ]);
    }
}
