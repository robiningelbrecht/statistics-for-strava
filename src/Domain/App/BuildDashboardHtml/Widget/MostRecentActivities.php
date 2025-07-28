<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml\Widget;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Environment;

final readonly class MostRecentActivities implements Widget
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
        private Environment $twig,
    ) {
    }

    public function render(SerializableDateTime $now): string
    {
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();

        return $this->twig->load('html/dashboard/widget/most-recent-activities.html.twig')->render([
            'mostRecentActivities' => $allActivities->slice(0, 5),
        ]);
    }
}
