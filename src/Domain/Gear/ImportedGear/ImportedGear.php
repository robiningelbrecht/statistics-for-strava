<?php

namespace App\Domain\Gear\ImportedGear;

use App\Domain\Activity\ActivityTypes;
use App\Domain\Gear\Gear;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearType;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;

#[ORM\Entity]
#[ORM\Table(name: 'Gear')]
#[ORM\Index(name: 'Gear_type', columns: ['type'])]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', options: ['default' => GearType::IMPORTED->value])]
class ImportedGear implements Gear
{
    private string $imageSrc;
    private ActivityTypes $activityTypes;
    private ?Money $purchasePrice = null;

    final private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly GearId $gearId,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $createdOn,
        #[ORM\Column(type: 'integer')]
        private readonly Meter $distanceInMeter,
        #[ORM\Column(type: 'string')]
        private readonly string $name,
        #[ORM\Column(type: 'boolean')]
        private readonly bool $isRetired,
    ) {
        $this->activityTypes = ActivityTypes::empty();
    }

    public static function create(
        GearId $gearId,
        Meter $distanceInMeter,
        SerializableDateTime $createdOn,
        string $name,
        bool $isRetired,
    ): static {
        return new static(
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
    ): static {
        return new static(
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

    public function withName(string $name): self
    {
        return clone ($this, [
            'name' => $name,
        ]);
    }

    public function getOriginalName(): string
    {
        return $this->name;
    }

    public function getName(): string
    {
        return sprintf('%s%s', $this->name, $this->isRetired() ? ' ☠️' : '');
    }

    public function getDistance(): Kilometer
    {
        return $this->distanceInMeter->toKilometer();
    }

    public function isRetired(): bool
    {
        return $this->isRetired;
    }

    public function withIsRetired(bool $isRetired): static
    {
        return clone ($this, [
            'isRetired' => $isRetired,
        ]);
    }

    public function withDistance(Meter $distance): static
    {
        return clone ($this, [
            'distanceInMeter' => $distance,
        ]);
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

    public function withImageSrc(string $imageSrc): static
    {
        return clone ($this, [
            'imageSrc' => $imageSrc,
        ]);
    }

    public function getActivityTypes(): ActivityTypes
    {
        return $this->activityTypes;
    }

    public function withActivityTypes(ActivityTypes $activityTypes): static
    {
        return clone ($this, [
            'activityTypes' => $activityTypes,
        ]);
    }

    public function getPurchasePrice(): ?Money
    {
        return $this->purchasePrice;
    }

    public function withPurchasePrice(Money $purchasePrice): static
    {
        return clone ($this, [
            'purchasePrice' => $purchasePrice,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function exportForAITooling(): array
    {
        return [
            'id' => $this->getId()->toUnprefixedString(),
            'totalDistanceInKilometer' => $this->getDistance(),
            'name' => $this->getName(),
            'isRetired' => $this->isRetired(),
        ];
    }
}
