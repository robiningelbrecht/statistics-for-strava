<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindElevationPerMonth;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;

final readonly class FindElevationPerMonthResponse implements Response
{
    public function __construct(
        /** @var array<int, array{0: int, 1: SportType, 2: Meter}> */
        private array $elevationPerMonth,
        private Meter $totalElevation,
    ) {
    }

    /**
     * @return array<int, array{0: int, 1: SportType, 2: Meter}>
     */
    public function getElevationPerMonth(): array
    {
        return $this->elevationPerMonth;
    }

    public function getTotalElevation(): Meter
    {
        return $this->totalElevation;
    }
}
