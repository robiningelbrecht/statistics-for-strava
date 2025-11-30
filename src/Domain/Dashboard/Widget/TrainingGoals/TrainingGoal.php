<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingGoals;

use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Challenge\Consistency\ProvideGoalConverters;
use App\Infrastructure\ValueObject\Measurement\ProvideUnitFromScalar;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class TrainingGoal
{
    use ProvideUnitFromScalar;
    use ProvideGoalConverters;

    private function __construct(
        private string $label,
        private bool $isEnabled,
        private TrainingGoalType $type,
        private TrainingGoalPeriod $period,
        private Unit $goal,
        private SportTypes $sportTypesToInclude,
    ) {
    }

    public static function create(
        string $label,
        bool $isEnabled,
        TrainingGoalType $type,
        TrainingGoalPeriod $period,
        float $goal,
        string $unit,
        SportTypes $sportTypesToInclude,
    ): self {
        return new self(
            label: $label,
            isEnabled: $isEnabled,
            type: $type,
            period: $period,
            goal: self::createUnitFromScalars(
                value: $goal,
                unit: $unit,
            ),
            sportTypesToInclude: $sportTypesToInclude,
        );
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getType(): TrainingGoalType
    {
        return $this->type;
    }

    public function getPeriod(): TrainingGoalPeriod
    {
        return $this->period;
    }

    public function getGoal(): Unit
    {
        return $this->goal;
    }

    public function getSportTypesToInclude(): SportTypes
    {
        return $this->sportTypesToInclude;
    }
}
