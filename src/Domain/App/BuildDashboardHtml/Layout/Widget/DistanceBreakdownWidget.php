<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml\Layout\Widget;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\DistanceBreakdown;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Environment;

final readonly class DistanceBreakdownWidget implements Widget
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
        private Environment $twig,
        private UnitSystem $unitSystem,
    ) {
    }

    public function render(SerializableDateTime $now): string
    {
        $activitiesPerActivityType = $this->activitiesEnricher->getActivitiesPerActivityType();

        $distanceBreakdowns = [];
        foreach ($activitiesPerActivityType as $activityType => $activities) {
            if ($activities->isEmpty()) {
                continue;
            }

            $activityType = ActivityType::from($activityType);
            if (!$activityType->supportsDistanceBreakdownStats()) {
                continue;
            }

            $distanceBreakdown = DistanceBreakdown::create(
                activities: $activitiesPerActivityType[$activityType->value],
                unitSystem: $this->unitSystem
            );

            if ($build = $distanceBreakdown->build()) {
                $distanceBreakdowns[$activityType->value] = $build;
            }
        }

        return $this->twig->load('html/dashboard/widget/widget--distance-breakdown.html.twig')->render([
            'distanceBreakdowns' => $distanceBreakdowns,
        ]);
    }
}
