<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindMovingTimePerDay;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindMovingTimePerDayResponse implements Response
{
    public function __construct(
        /** @var array<string, int> */
        private array $movingTimePerDay,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function getMovingTimePerDay(): array
    {
        return $this->movingTimePerDay;
    }

    /**
     * @return array<string, int>
     */
    public function getMovingTimePerDayInMinutes(): array
    {
        return array_map(
            fn (int $seconds) => (int) round($seconds / 60),
            $this->movingTimePerDay,
        );
    }
}
