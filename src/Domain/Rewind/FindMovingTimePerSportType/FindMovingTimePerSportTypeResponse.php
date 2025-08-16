<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindMovingTimePerSportType;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindMovingTimePerSportTypeResponse implements Response
{
    public function __construct(
        /** @var array<string, int> */
        private array $movingTimePerSportType,
        private int $totalMovingTime,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function getMovingTimePerSportType(): array
    {
        return $this->movingTimePerSportType;
    }

    public function getTotalMovingTime(): int
    {
        return $this->totalMovingTime;
    }
}
