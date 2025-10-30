<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\WeeklyGoals;

use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Measurement\Unit;
use App\Infrastructure\ValueObject\String\Name;

final readonly class WeeklyGoal
{
    public const string KILOMETER = 'km';
    public const string METER = 'm';
    public const string MILES = 'mi';
    public const string FOOT = 'ft';
    public const string HOUR = 'hour';
    public const string MINUTE = 'minute';

    private function __construct(
        private string $label,
        private bool $isEnabled,
        private WeeklyGoalType $type,
        private float $goal,
        private Unit $unit,
        private SportTypes $sportTypesToInclude,
    ) {
    }

    public static function create(
        string $label,
        bool $isEnabled,
        WeeklyGoalType $type,
        float $goal,
        Unit $unit,
        SportTypes $sportTypesToInclude,
    ): self {
        return new self(
            label: $label,
            isEnabled: $isEnabled,
            type: $type,
            goal: $goal,
            unit: $unit,
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

    public function getGoal(): float
    {
        return $this->goal;
    }

    public function getUnit(): Unit
    {
        return $this->unit;
    }

    public function getSportTypesToInclude(): SportTypes
    {
        return $this->sportTypesToInclude;
    }
}
