<?php

declare(strict_types=1);

namespace App\Application\Build\BuildActivitiesHtml;

use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;

final readonly class BuildActivityGapSplitData
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
