<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Activity\WeekdayStats\WeekdayStats;
use App\Domain\Activity\WeekdayStats\WeekdayStatsChart;
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

    public function guardValidConfiguration(array $config): void
    {
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();

        $weekdayStats = WeekdayStats::create(
            activities: $allActivities,
            translator: $this->translator
        );

        return $this->twig->load('html/dashboard/widget/widget--weekday-stats.html.twig')->render([
            'weekdayStats' => $weekdayStats,
            'weekdayStatsChart' => Json::encode(
                WeekdayStatsChart::create($weekdayStats)->build(),
            ),
        ]);
    }
}
