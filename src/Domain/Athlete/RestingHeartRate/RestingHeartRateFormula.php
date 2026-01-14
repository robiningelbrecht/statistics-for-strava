<?php

namespace App\Domain\Athlete\RestingHeartRate;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface RestingHeartRateFormula
{
    public function calculate(int $age, SerializableDateTime $on): int;
}
