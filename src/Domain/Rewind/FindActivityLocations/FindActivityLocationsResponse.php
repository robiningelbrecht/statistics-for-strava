<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindActivityLocations;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindActivityLocationsResponse implements Response
{
    public function __construct(
        /** @var array<int,array{0: float, 1: float, 2: int}> */
        private array $activityLocations,
    ) {
    }

    /**
     * @return array<int, array{0: float, 1: float, 2: int}>
     */
    public function getActivityLocations(): array
    {
        return $this->activityLocations;
    }
}
