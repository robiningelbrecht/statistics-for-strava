<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream\Metric;

use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<ActivityStreamMetric>
 */
class ActivityStreamMetrics extends Collection
{
    public function getItemClassName(): string
    {
        return ActivityStreamMetric::class;
    }

    public function filterOnStreamType(StreamType $streamType): ?ActivityStreamMetric
    {
        return $this->filter(
            fn (ActivityStreamMetric $metric): bool => $metric->getStreamType() === $streamType
        )->getFirst();
    }
}
