<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml\Layout\Widget;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class IntroTextWidget implements Widget
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
        private TranslatorInterface $translator,
        private Environment $twig,
    ) {
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $activityTotals = ActivityTotals::getInstance(
            activities: $allActivities,
            now: $now,
            translator: $this->translator,
        );

        return $this->twig->load('html/dashboard/widget/widget--intro-text.html.twig')->render([
            'intro' => $activityTotals,
        ]);
    }
}
