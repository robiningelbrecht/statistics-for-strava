<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\Activity;
use NeuronAI\Tools\Tool;

final class GetAllActivitiesSummary extends Tool
{
    public function __construct(
        private readonly ActivitiesEnricher $activitiesEnricher,
    ) {
        parent::__construct(
            'get_user_activities_summary',
            'Retrieves all activity ids from the database sorted from new => old and adds some summary data',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $summary = $allActivities
            ->map(fn (Activity $activity) => [
                'id' => $activity->getId()->toUnprefixedString(),
                'on' => $activity->getStartDate()->format('Y-m-d'),
            ]);

        return [
            'allActivitiesSummary' => $summary,
            'totalActivityCount' => count($allActivities),
        ];
    }
}
