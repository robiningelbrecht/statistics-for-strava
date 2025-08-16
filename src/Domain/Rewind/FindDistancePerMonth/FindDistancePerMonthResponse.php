<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindDistancePerMonth;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;

final readonly class FindDistancePerMonthResponse implements Response
{
    public function __construct(
        /** @var array<int, array{0: int, 1: SportType, 2: Kilometer}> */
        private array $distancePerMonth,
        private Kilometer $totalDistance,
    ) {
    }

    /**
     * @return array<int, array{0: int, 1: SportType, 2: Kilometer}>
     */
    public function getDistancePerMonth(): array
    {
        return $this->distancePerMonth;
    }

    public function getTotalDistance(): Kilometer
    {
        return $this->totalDistance;
    }
}
