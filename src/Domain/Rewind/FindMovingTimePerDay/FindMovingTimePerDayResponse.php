<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindMovingTimePerDay;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindMovingTimePerDayResponse implements Response
{
    public function __construct(
        /** @var array<string, int> */
        private array $movingTimePerDay,
        private int $totalActivityCount,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function getMovingTimePerDay(): array
    {
        return $this->movingTimePerDay;
    }

    public function getTotalActivityCount(): int
    {
        return $this->totalActivityCount;
    }
}
