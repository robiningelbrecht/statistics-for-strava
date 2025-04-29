<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;

final readonly class RewindCutOffDate
{
    private function __construct(
        private SerializableDateTime $cutOffDateTime,
    ) {
    }

    public static function fromYear(Year $year): self
    {
        return new self(SerializableDateTime::fromString(
            sprintf('%s-12-24 00:00:00', $year))
        );
    }

    public function isBefore(SerializableDateTime $now): bool
    {
        return $this->cutOffDateTime->isBefore($now);
    }
}
