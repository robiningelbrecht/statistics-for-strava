<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\WeeklyGoals\FindWeeklyGoalMetrics;

use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;

final readonly class FindWeeklyGoalMetricsResponse implements Response
{
    public function __construct(
        private Kilometer $distance,
        private Meter $elevation,
        private Seconds $movingTime,
    ) {
    }

    public function getDistance(): Kilometer
    {
        return $this->distance;
    }

    public function getElevation(): Meter
    {
        return $this->elevation;
    }

    public function getMovingTime(): Seconds
    {
        return $this->movingTime;
    }
}
