<?php

declare(strict_types=1);

namespace App\Domain\Athlete\MaxHeartRate;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface MaxHeartRateFormula
{
    public function calculate(int $age, SerializableDateTime $on): int;
}
