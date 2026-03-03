<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Mile;

final readonly class GearDistanceContext implements MilestoneContext
{
    public function __construct(
        private string $gearName,
        private Kilometer|Mile $threshold,
        private Kilometer|Mile $totalDistance,
    ) {
    }

    public function getGearName(): string
    {
        return $this->gearName;
    }

    public function getThreshold(): Kilometer|Mile
    {
        return $this->threshold;
    }

    public function getTotalDistance(): Kilometer|Mile
    {
        return $this->totalDistance;
    }
}
