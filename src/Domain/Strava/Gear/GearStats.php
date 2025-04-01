<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class GearStats
{
    /** @var array<mixed> */
    private array $stats = [];

    private function __construct()
    {
    }

    public static function empty(): self
    {
        return new self();
    }

    public function addStat(GearId $gearId, SerializableDateTime $date, Kilometer $distance): void
    {
        $this->stats[(string) $gearId][$date->format('Ymd')] = $distance;
    }

    public function getDistanceFor(GearId $gearId, SerializableDateTime $date): ?Kilometer
    {
        return $this->stats[(string) $gearId][$date->format('Ymd')] ?? null;
    }
}
