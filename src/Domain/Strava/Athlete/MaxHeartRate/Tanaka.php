<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\MaxHeartRate;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Tanaka implements MaxHeartRateFormula
{
    public function calculate(int $age, SerializableDateTime $on): int
    {
        return (int) round(208 - (0.7 * $age));
    }
}
