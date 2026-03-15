<?php

declare(strict_types=1);

namespace App\Domain\Gear\RecordingDevice;

use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
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
    ): self {
        return new self(
            name: $name,
            timeTracked: $timeTracked,
            distanceTracked: $distanceTracked,
            elevationTracked: $elevationTracked,
            numberOfWorkouts: $activityCount,
            purchasePrice: null,
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
        return $this->formatDurationAsHumanString($this->getTimeTracked()->toInt());
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

    public function withPurchasePrice(?Money $purchasePrice): self
    {
        return clone ($this, [
            'purchasePrice' => $purchasePrice,
        ]);
    }

    public function getPurchasePrice(): ?Money
    {
        return $this->purchasePrice;
    }

    public function getRelativeCostPerHour(): ?Money
    {
        $timeTrackedInHours = $this->getTimeTracked()->toHour()->toInt();

        return $this->getPurchasePrice()?->divide($timeTrackedInHours > 0 ? $timeTrackedInHours : 1);
    }

    public function getRelativeCostPerWorkout(): ?Money
    {
        return $this->getPurchasePrice()?->divide($this->getNumberOfWorkouts() > 0 ? $this->getNumberOfWorkouts() : 1);
    }

    public function getRelativeCostPerDistanceUnit(UnitSystem $unitSystem): ?Money
    {
        $distance = $this->getDistanceTracked()->toUnitSystem($unitSystem)->toInt();

        return $this->getPurchasePrice()?->divide($distance > 0 ? $distance : 1);
    }
}
