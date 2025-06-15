<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\HeartRateZone;

final readonly class HeartRateZone
{
    public function __construct(
        private int $from,
        private ?int $to,
    ) {
    }

    public function getFrom(): int
    {
        return $this->from;
    }

    public function getTo(): ?int
    {
        return $this->to;
    }
}
