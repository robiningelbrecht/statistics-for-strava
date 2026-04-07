<?php

declare(strict_types=1);

namespace App\Domain\Athlete\RestingHeartRate;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Fixed implements RestingHeartRateFormula
{
    public function __construct(
        private int $restingHeartRate,
    ) {
    }

    public function calculate(int $age, SerializableDateTime $on): int
    {
        return $this->restingHeartRate;
    }
}
