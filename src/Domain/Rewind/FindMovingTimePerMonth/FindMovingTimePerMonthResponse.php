<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindMovingTimePerMonth;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\CQRS\Query\Response;

final readonly class FindMovingTimePerMonthResponse implements Response
{
    public function __construct(
        /** @var array<int, array{0: int, 1: SportType, 2: int}> */
        private array $movingTimePerMonth,
        private int $totalMovingTime,
    ) {
    }

    /**
     * @return array<int, array{0: int, 1: SportType, 2: int}>
     */
    public function getMovingTimePerMonth(): array
    {
        return $this->movingTimePerMonth;
    }

    public function getTotalMovingTime(): int
    {
        return $this->totalMovingTime;
    }
}
