<?php

namespace App\Domain\Strava\Gear;

use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Gear
{
    private string $imageSrc;

    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly GearId $gearId,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $createdOn,
        #[ORM\Column(type: 'integer')]
        private Meter $distanceInMeter,
        #[ORM\Column(type: 'string')]
        private string $name,
        #[ORM\Column(type: 'boolean')]
        private bool $isRetired,
    ) {
    }

    public static function create(
        GearId $gearId,
        Meter $distanceInMeter,
        SerializableDateTime $createdOn,
        string $name,
        bool $isRetired,
    ): self {
        return new self(
            gearId: $gearId,
            createdOn: $createdOn,
            distanceInMeter: $distanceInMeter,
            name: $name,
            isRetired: $isRetired,
        );
    }

    public static function fromState(
        GearId $gearId,
        Meter $distanceInMeter,
        SerializableDateTime $createdOn,
        string $name,
        bool $isRetired,
    ): self {
        return new self(
            gearId: $gearId,
            createdOn: $createdOn,
            distanceInMeter: $distanceInMeter,
            name: $name,
            isRetired: $isRetired,
        );
    }

    public function getId(): GearId
    {
        return $this->gearId;
    }

    public function updateName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getOriginalName(): string
    {
        return $this->name;
    }

    public function getName(): string
    {
        return sprintf('%s%s', $this->name, $this->isRetired() ? ' ☠️' : '');
    }

    public function getSanitizedName(): string
    {
        return htmlspecialchars($this->getName());
    }

    public function getDistance(): Kilometer
    {
        return Kilometer::from($this->distanceInMeter->toInt() / 1000);
    }

    public function isRetired(): bool
    {
        return $this->isRetired;
    }

    public function updateIsRetired(bool $isRetired): self
    {
        $this->isRetired = $isRetired;

        return $this;
    }

    public function updateDistance(Meter $distance): self
    {
        $this->distanceInMeter = $distance;

        return $this;
    }

    public function getCreatedOn(): SerializableDateTime
    {
        return $this->createdOn;
    }

    public function getImageSrc(): ?string
    {
        if (!isset($this->imageSrc)) {
            return null;
        }

        return $this->imageSrc;
    }

    public function enrichWithImageSrc(string $imageSrc): self
    {
        $this->imageSrc = $imageSrc;

        return $this;
    }
}
