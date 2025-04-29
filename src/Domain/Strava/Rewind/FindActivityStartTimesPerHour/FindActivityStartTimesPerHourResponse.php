<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindActivityStartTimesPerHour;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindActivityStartTimesPerHourResponse implements Response
{
    public function __construct(
        /** @var array<int, int> */
        private array $activityStartTimesPerHour,
    ) {
    }

    /**
     * @return array<int, int>
     */
    public function getActivityStartTimesPerHour(): array
    {
        return $this->activityStartTimesPerHour;
    }
}
