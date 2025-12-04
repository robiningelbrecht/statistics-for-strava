<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingGoals\FindTrainingGoalMetrics;

use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

/**
 * @implements Query<\App\Domain\Dashboard\Widget\TrainingGoals\FindTrainingGoalMetrics\FindTrainingGoalMetricsResponse>
 */
final readonly class FindTrainingGoalMetrics implements Query
{
    public function __construct(
        private SportTypes $sportTypes,
        private SerializableDateTime $from,
        private SerializableDateTime $to,
    ) {
    }

    public function getSportTypes(): SportTypes
    {
        return $this->sportTypes;
    }

    public function getFrom(): SerializableDateTime
    {
        return $this->from;
    }

    public function getTo(): SerializableDateTime
    {
        return $this->to;
    }
}
