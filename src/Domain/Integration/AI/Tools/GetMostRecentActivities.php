<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tools;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\Activity;
use App\Infrastructure\Serialization\Json;
use NeuronAI\Tools\Tool;

final class GetMostRecentActivities extends Tool
{
    public function __construct(
        private readonly ActivitiesEnricher $activitiesEnricher,
    ) {
        parent::__construct(
            'get_user_workout_history',
            'Retrieves the workout history of the user from the database',
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function __invoke(): array
    {
        $activities = $this->activitiesEnricher->getEnrichedActivities()->slice(0, 10);

        return $activities->map(fn (Activity $activity) => Json::encode($activity));
    }
}
