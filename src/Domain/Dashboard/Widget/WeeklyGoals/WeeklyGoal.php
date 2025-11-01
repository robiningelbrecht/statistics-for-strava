<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\WeeklyGoals;

use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Challenge\Consistency\ProvideGoalConverters;
use App\Infrastructure\ValueObject\Measurement\ProvideUnitFromScalar;
use App\Infrastructure\ValueObject\Measurement\Unit;
use App\Infrastructure\ValueObject\String\Name;

final readonly class WeeklyGoal
{
    use ProvideUnitFromScalar;
    use ProvideGoalConverters;

    private function __construct(
        private string $label,
        private bool $isEnabled,
        private WeeklyGoalType $type,
        private Unit $goal,
        private SportTypes $sportTypesToInclude,
    ) {
    }

    public static function create(
        string $label,
        bool $isEnabled,
        WeeklyGoalType $type,
        float $goal,
        string $unit,
        SportTypes $sportTypesToInclude,
    ): self {
        return new self(
            label: $label,
            isEnabled: $isEnabled,
            type: $type,
            goal: self::createUnitFromScalars(
                value: $goal,
                unit: $unit,
            ),
            sportTypesToInclude: $sportTypesToInclude,
        );
    }

    public function getId(): string
    {
        return Name::fromString($this->label)->kebabCase();
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getType(): WeeklyGoalType
    {
        return $this->type;
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
