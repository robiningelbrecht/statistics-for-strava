<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Activity\Activity;
use NeuronAI\Tools\Tool;

final class GetAllActivitiesSummary extends Tool
{
    public function __construct(
        private readonly ActivitiesEnricher $activitiesEnricher,
    ) {
        parent::__construct(
            'get_user_activities_summary',
            <<<DESC
            Retrieves a list of all activity IDs for the user, sorted from newest to oldest, along with summary data for each activity.
            Use this tool when the user requests an overview of their past activities.
            Returns basic information such as activity ID and date.
            DESC
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
