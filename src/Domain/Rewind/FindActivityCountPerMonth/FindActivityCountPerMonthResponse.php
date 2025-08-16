<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindActivityCountPerMonth;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\CQRS\Query\Response;

final readonly class FindActivityCountPerMonthResponse implements Response
{
    public function __construct(
        /** @var array<int, array{0: int, 1: SportType, 2: int}> */
        private array $activityCountPerMonth,
    ) {
    }

    /**
     * @return array<int, array{0: int, 1: SportType, 2: int}>
     */
    public function getActivityCountPerMonth(): array
    {
        return $this->activityCountPerMonth;
    }
}
