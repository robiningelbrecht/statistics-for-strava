<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\ImportedGear;

use App\Domain\Gear\GearId;
use App\Domain\Gear\ImportedGear\ImportedGear;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ImportedGearBuilder
{
    private GearId $gearId;
    private SerializableDateTime $createdOn;
    private Meter $distanceInMeter;
    private string $name = 'Existing gear';
    private bool $isRetired = false;
    private Seconds $movingTime;
    private Meter $elevation;
    private int $numberOfActivities = 0;
    private int $totalCalories = 0;

    private function __construct()
    {
        $this->gearId = GearId::fromUnprefixed('1');
        $this->createdOn = SerializableDateTime::fromString('2023-10-10');
        $this->distanceInMeter = Meter::from(10023);
        $this->movingTime = Seconds::zero();
        $this->elevation = Meter::zero();
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): ImportedGear
    {
        return ImportedGear::fromState(
            gearId: $this->gearId,
            distanceInMeter: $this->distanceInMeter,
            createdOn: $this->createdOn,
            name: $this->name,
            isRetired: $this->isRetired,
            movingTime: $this->movingTime,
            elevation: $this->elevation,
            numberOfActivities: $this->numberOfActivities,
            totalCalories: $this->totalCalories,
        );
    }

    public function withMovingTime(Seconds $movingTime): self
    {
        $this->movingTime = $movingTime;

        return $this;
    }

    public function withElevation(Meter $elevation): self
    {
        $this->elevation = $elevation;

        return $this;
    }

    public function withNumberOfActivities(int $numberOfActivities): self
    {
        $this->numberOfActivities = $numberOfActivities;

        return $this;
    }

    public function withTotalCalories(int $totalCalories): self
    {
        $this->totalCalories = $totalCalories;

        return $this;
    }

    public function withGearId(GearId $gearId): self
    {
        $this->gearId = $gearId;

        return $this;
    }

    public function withDistanceInMeter(Meter $distanceInMeter): self
    {
        $this->distanceInMeter = $distanceInMeter;

        return $this;
    }

    public function withCreatedOn(SerializableDateTime $createdOn): self
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withIsRetired(bool $isRetired): self
    {
        $this->isRetired = $isRetired;

        return $this;
    }
}
