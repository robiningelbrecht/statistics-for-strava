<?php

declare(strict_types=1);

namespace App\Domain\Activity\YearlyDistance\FindYearlyStatsPerDay;

use App\Domain\Activity\ActivityType;
use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class FindYearlyStatsPerDayResponse implements Response
{
    /** @var array<string, array<string|int, Kilometer>> */
    private array $stats = [];

    private function __construct()
    {
    }

    public static function empty(): self
    {
        return new self();
    }

    public function add(SerializableDateTime $date, ActivityType $activityType, Kilometer $distance): void
    {
        $this->stats[$activityType->value][$date->format('Ymd')] = $distance;
    }

    public function getDistanceFor(SerializableDateTime $date, ActivityType $activityType): ?Kilometer
    {
        return $this->stats[$activityType->value][$date->format('Ymd')] ?? null;
    }
}
