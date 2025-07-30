<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml\Layout\Widget;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\DaytimeStats\DaytimeStats;
use App\Domain\Strava\Activity\DaytimeStats\DaytimeStatsCharts;
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

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $dayTimeStats = DaytimeStats::create($allActivities);

        return $this->twig->load('html/dashboard/widget/widget--day-time-stats.html.twig')->render([
            'daytimeStatsChart' => Json::encode(
                DaytimeStatsCharts::create(
                    daytimeStats: $dayTimeStats,
                    translator: $this->translator,
                )->build(),
            ),
            'daytimeStats' => $dayTimeStats,
        ]);
    }
}
