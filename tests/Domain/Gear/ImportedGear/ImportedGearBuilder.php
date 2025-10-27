<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\ImportedGear;

use App\Domain\Gear\GearId;
use App\Domain\Gear\GearType;
use App\Domain\Gear\ImportedGear\ImportedGear;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ImportedGearBuilder
{
    private GearId $gearId;
    private SerializableDateTime $createdOn;
    private Meter $distanceInMeter;
    private string $name;
    private bool $isRetired;

    private function __construct()
    {
        $this->gearId = GearId::fromUnprefixed('1');
        $this->createdOn = SerializableDateTime::fromString('2023-10-10');
        $this->distanceInMeter = Meter::from(10023);
        $this->name = 'Existing gear';
        $this->isRetired = false;
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): ImportedGear
    {
        return ImportedGear::fromState(
            gearId: $this->gearId,
            type: GearType::IMPORTED,
            distanceInMeter: $this->distanceInMeter,
            createdOn: $this->createdOn,
            name: $this->name,
            isRetired: $this->isRetired
        );
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
