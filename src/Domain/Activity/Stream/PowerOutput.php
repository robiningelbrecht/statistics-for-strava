<?php

namespace App\Domain\Activity\Stream;

use App\Domain\Activity\Activity;

final readonly class PowerOutput
{
    private function __construct(
        private int $timeIntervalInSeconds,
        private string $formattedTimeInterval,
        private int $power,
        private float $relativePower,
        private ?Activity $activity = null,
    ) {
    }

    public static function fromState(
        int $timeIntervalInSeconds,
        string $formattedTimeInterval,
        int $power,
        float $relativePower,
        ?Activity $activity = null,
    ): self {
        return new self(
            timeIntervalInSeconds: $timeIntervalInSeconds,
            formattedTimeInterval: $formattedTimeInterval,
            power: $power,
            relativePower: $relativePower,
            activity: $activity
        );
    }

    public function getTimeIntervalInSeconds(): int
    {
        return $this->timeIntervalInSeconds;
    }

    public function getFormattedTimeInterval(): string
    {
        return $this->formattedTimeInterval;
    }

    public function getPower(): int
    {
        return $this->power;
    }

    public function getRelativePower(): float
    {
        return $this->relativePower;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }
}
