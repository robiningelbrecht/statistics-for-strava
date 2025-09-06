<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Activity\DaytimeStats\DaytimeStats;
use App\Domain\Activity\DaytimeStats\DaytimeStatsCharts;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Environment;

final readonly class DayTimeStatsWidget implements Widget
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
        private Environment $twig,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty();
    }

    public function guardValidConfiguration(array $config): void
    {
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $dayTimeStats = DaytimeStats::create($allActivities);

        return $this->twig->load('html/dashboard/widget/widget--day-time-stats.html.twig')->render([
            'daytimeStatsChart' => Json::encode(
                DaytimeStatsCharts::create(
                    daytimeStats: $dayTimeStats,
                )->build(),
            ),
            'daytimeStats' => $dayTimeStats,
        ]);
    }
}
