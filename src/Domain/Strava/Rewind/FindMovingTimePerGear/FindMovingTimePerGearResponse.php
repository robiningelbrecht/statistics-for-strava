<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindMovingTimePerGear;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindMovingTimePerGearResponse implements Response
{
    public function __construct(
        /** @var array<string, int> */
        private array $movingTimePerGear,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function getMovingTimePerGear(): array
    {
        return $this->movingTimePerGear;
    }
}
