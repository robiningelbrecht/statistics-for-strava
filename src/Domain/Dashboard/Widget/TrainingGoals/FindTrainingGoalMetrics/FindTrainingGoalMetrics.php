<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingGoals\FindTrainingGoalMetrics;

use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Calendar\Week;
use App\Infrastructure\CQRS\Query\Query;

/**
 * @implements Query<\App\Domain\Dashboard\Widget\TrainingGoals\FindTrainingGoalMetrics\FindTrainingGoalMetricsResponse>
 */
final readonly class FindTrainingGoalMetrics implements Query
{
    public function __construct(
        private SportTypes $sportTypes,
        private Week $week,
    ) {
    }

    public function getSportTypes(): SportTypes
    {
        return $this->sportTypes;
    }

    public function getWeek(): Week
    {
        return $this->week;
    }
}
