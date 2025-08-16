<?php

declare(strict_types=1);

namespace App\Domain\Activity\ImportActivities;

final class NumberOfNewActivitiesToProcessPerImport
{
    private int $numberOfActivitiesProcessed = 0;

    private function __construct(
        private readonly int $value,
    ) {
        if ($this->value <= 0) {
            throw new \InvalidArgumentException('NumberOfNewActivitiesToProcessPerImport must be greater than 0');
        }
    }

    public static function fromInt(int $value): NumberOfNewActivitiesToProcessPerImport
    {
        return new self($value);
    }

    public function increaseNumberOfProcessedActivities(): void
    {
        ++$this->numberOfActivitiesProcessed;
    }

    public function maxNumberProcessed(): bool
    {
        return $this->numberOfActivitiesProcessed >= $this->value;
    }
}
