<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindCaloriesBurnt;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindCaloriesBurntResponse implements Response
{
    public function __construct(
        private int $calories,
    ) {
    }

    public function getCalories(): int
    {
        return $this->calories;
    }
}
