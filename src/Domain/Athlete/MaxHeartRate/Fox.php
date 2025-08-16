<?php

declare(strict_types=1);

namespace App\Domain\Athlete\MaxHeartRate;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Fox implements MaxHeartRateFormula
{
    public function calculate(int $age, SerializableDateTime $on): int
    {
        return 220 - $age;
    }
}
