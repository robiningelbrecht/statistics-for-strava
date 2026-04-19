<?php

declare(strict_types=1);

namespace App\Domain\Activity\Gap;

use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;

final readonly class ActivityGapSplit
{
    public function __construct(
        private SecPerKm $gapPaceInSeconds,
    ) {
    }

    public function getGapPaceInSeconds(): SecPerKm
    {
        return $this->gapPaceInSeconds;
    }
}
