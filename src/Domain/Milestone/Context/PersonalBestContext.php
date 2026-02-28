<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class PersonalBestContext implements MilestoneContext
{
    public function __construct(
        private Unit $distance,
        private Seconds $time,
        private ?Seconds $previousTime,
    ) {
    }

    public function getDistance(): Unit
    {
        return $this->distance;
    }

    public function getTime(): Seconds
    {
        return $this->time;
    }

    public function getPreviousTime(): ?Seconds
    {
        return $this->previousTime;
    }
}
