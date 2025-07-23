<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindPersonalRecordsPerMonth;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindPersonalRecordsPerMonthResponse implements Response
{
    public function __construct(
        /** @var array<int, array{0: int, 1: int}> */
        private array $personalRecordsPerMonth,
    ) {
    }

    /**
     * @return array<int, array{0: int, 1: int}>
     */
    public function getPersonalRecordsPerMonth(): array
    {
        return $this->personalRecordsPerMonth;
    }
}
