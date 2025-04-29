<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\MaxHeartRate;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface MaxHeartRateFormula
{
    public function calculate(int $age, SerializableDateTime $on): int;
}
