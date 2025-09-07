<?php

declare(strict_types=1);

namespace App\Domain\Activity\Grid\FindCaloriesBurnedPerDay;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindCaloriesBurnedPerDayResponse implements Response
{
    public function __construct(
        /** @var array<string, int> */
        private array $caloriesBurnedTimePerDay,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function getCaloriesBurnedPerDay(): array
    {
        return $this->caloriesBurnedTimePerDay;
    }
}
