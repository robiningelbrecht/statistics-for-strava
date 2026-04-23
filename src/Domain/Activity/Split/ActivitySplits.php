<?php

declare(strict_types=1);

namespace App\Domain\Activity\Split;

use App\Infrastructure\ValueObject\Collection;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;

/**
 * @extends Collection<ActivitySplit>
 */
final class ActivitySplits extends Collection
{
    public function getItemClassName(): string
    {
        return ActivitySplit::class;
    }

    public function getOverallGapPaceInSecondsPerKm(): ?SecPerKm
    {
        $totalWeightedGap = 0.0;
        $totalDistance = 0.0;

        foreach ($this as $split) {
            $gapPace = $split->getGapPaceInSecondsPerKm();
            if (!$gapPace instanceof SecPerKm) {
                continue;
            }
            $distance = $split->getDistance()->toFloat();
            $totalWeightedGap += $gapPace->toFloat() * $distance;
            $totalDistance += $distance;
        }

        if ($totalDistance <= 0.0) {
            return null;
        }

        return SecPerKm::from($totalWeightedGap / $totalDistance);
    }
}
