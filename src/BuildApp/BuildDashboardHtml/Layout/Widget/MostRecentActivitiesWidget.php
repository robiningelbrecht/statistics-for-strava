<?php

declare(strict_types=1);

namespace App\BuildApp\BuildDashboardHtml\Layout\Widget;

use App\Domain\Activity\ActivitiesEnricher;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Environment;

final readonly class MostRecentActivitiesWidget implements Widget
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

        return $this->twig->load('html/dashboard/widget/widget--most-recent-activities.html.twig')->render([
            'mostRecentActivities' => $allActivities->slice(0, 5),
        ]);
    }
}
