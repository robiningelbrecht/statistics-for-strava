<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\MaxHeartRate;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Astrand implements MaxHeartRateFormula
{
    public function calculate(int $age, SerializableDateTime $on): int
    {
        return (int) round(216.6 - (0.84 * $age));
    }
}
