<?php

namespace App\Domain\Gear\ImportedGear;

use App\Domain\Activity\ActivityTypes;
use App\Domain\Activity\SportType\SportTypes;
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
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', options: ['default' => GearType::IMPORTED->value])]
class ImportedGear implements Gear
{
    private string $imageSrc;
    private SportTypes $sportTypes;
    private ActivityTypes $activityTypes;
    private ?Money $purchasePrice = null;

    final private function __construct(
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
        private readonly GearType $type,
    ) {
        $this->sportTypes = SportTypes::empty();
        $this->activityTypes = ActivityTypes::empty();
    }

    public static function create(
        GearId $gearId,
        GearType $type,
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
            type: $type,
        );
    }

    public static function fromState(
        GearId $gearId,
        GearType $type,
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
            type: $type,
        );
    }

    public function getId(): GearId
    {
        return $this->gearId;
    }

    public function getType(): GearType
    {
        return $this->type;
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

    public function getDistance(): Kilometer
    {
        return $this->distanceInMeter->toKilometer();
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

    public function withImageSrc(string $imageSrc): self
    {
        return clone ($this, [
            'imageSrc' => $imageSrc,
        ]);
    }

    public function getSportTypes(): SportTypes
    {
        return $this->sportTypes;
    }

    public function getActivityTypes(): ActivityTypes
    {
        return $this->activityTypes;
    }

    public function withSportTypes(SportTypes $sportTypes): static
    {
        $this->sportTypes = $sportTypes;
        $activityTypes = ActivityTypes::empty();
        /** @var \App\Domain\Activity\SportType\SportType $sportType */
        foreach ($this->sportTypes as $sportType) {
            $activityType = $sportType->getActivityType();
            if ($activityTypes->has($activityType)) {
                continue;
            }
            $activityTypes->add($activityType);
        }

        return clone ($this, [
            'sportTypes' => $sportTypes,
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
