<?php

namespace App\Domain\Athlete\RestingHeartRate;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class HeuristicAgeBased implements RestingHeartRateFormula
{
    public function calculate(int $age, SerializableDateTime $on): int
    {
        return max(50, 75 - (int) (0.2 * $age));
    }
}
