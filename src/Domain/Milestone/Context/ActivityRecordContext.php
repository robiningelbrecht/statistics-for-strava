<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class ActivityRecordContext implements MilestoneContext
{
    public function __construct(
        private Unit $value,
        private ?Unit $previousValue,
    ) {
    }

    public function getValue(): Unit
    {
        return $this->value;
    }

    public function getPreviousValue(): ?Unit
    {
        return $this->previousValue;
    }
}
