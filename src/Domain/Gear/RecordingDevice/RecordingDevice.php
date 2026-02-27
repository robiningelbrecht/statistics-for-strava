<?php

declare(strict_types=1);

namespace App\Domain\Gear\RecordingDevice;

use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\String\Name;
use Money\Money;

final readonly class RecordingDevice
{
    use ProvideTimeFormats;

    private function __construct(
        private string $name,
        private Seconds $timeTracked,
        private Kilometer $distanceTracked,
        private Meter $elevationTracked,
        private int $numberOfWorkouts,
        private ?Money $purchasePrice,
    ) {
    }

    public static function fromState(
        string $name,
        Seconds $timeTracked,
        Kilometer $distanceTracked,
        Meter $elevationTracked,
        int $activityCount,
        ?Money $purchasePrice,
    ): self {
        return new self(
            name: $name,
            timeTracked: $timeTracked,
            distanceTracked: $distanceTracked,
            elevationTracked: $elevationTracked,
            numberOfWorkouts: $activityCount,
            purchasePrice: $purchasePrice,
        );
    }

    public function getId(): string
    {
        return Name::fromString($this->getName())->kebabCase();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTimeTracked(): Seconds
    {
        return $this->timeTracked;
    }

    public function getTimeTrackedFormatted(): string
    {
        return $this->formatVeryLongDurationForHumans($this->getTimeTracked()->toInt());
    }

    public function getDistanceTracked(): Kilometer
    {
        return $this->distanceTracked;
    }

    public function getElevationTracked(): Meter
    {
        return $this->elevationTracked;
    }

    public function getNumberOfWorkouts(): int
    {
        return $this->numberOfWorkouts;
    }
}
