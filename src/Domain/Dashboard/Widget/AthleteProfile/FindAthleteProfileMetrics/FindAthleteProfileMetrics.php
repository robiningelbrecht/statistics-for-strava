<?php

namespace App\Domain\Dashboard\Widget\AthleteProfile\FindAthleteProfileMetrics;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\DateRange;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

/**
 * @implements Query<\App\Domain\Dashboard\Widget\AthleteProfile\FindAthleteProfileMetrics\FindAthleteProfileMetricsResponse>
 */
final readonly class FindAthleteProfileMetrics implements Query
{
    public function __construct(
        private DateRange $dateRange,
    ) {
    }

    public function getFrom(): SerializableDateTime
    {
        return $this->dateRange->getFrom();
    }

    public function getTo(): SerializableDateTime
    {
        return $this->dateRange->getTill();
    }
}
