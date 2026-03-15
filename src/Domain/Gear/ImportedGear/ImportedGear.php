<?php

namespace App\Domain\Gear\ImportedGear;

use App\Domain\Activity\ActivityTypes;
use App\Domain\Gear\Gear;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearType;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Hour;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
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
    use ProvideTimeFormats;

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
        private readonly ?string $imageSrc,
        private readonly Seconds $movingTime,
        private readonly Meter $elevation,
        private readonly int $numberOfActivities,
        private readonly int $totalCalories,
        private readonly ActivityTypes $activityTypes,
        private readonly ?Money $purchasePrice,
    ) {
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
            imageSrc: null,
            movingTime: Seconds::zero(),
            elevation: Meter::zero(),
            numberOfActivities: 0,
            totalCalories: 0,
            activityTypes: ActivityTypes::empty(),
            purchasePrice: null,
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
            imageSrc: null,
            movingTime: Seconds::zero(),
            elevation: Meter::zero(),
            numberOfActivities: 0,
            totalCalories: 0,
            activityTypes: ActivityTypes::empty(),
            purchasePrice: null,
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

    public function getMovingTime(): Seconds
    {
        return $this->movingTime;
    }

    public function withMovingTime(Seconds $movingTime): static
    {
        return clone ($this, [
            'movingTime' => $movingTime,
        ]);
    }

    public function getElevation(): Meter
    {
        return $this->elevation;
    }

    public function withElevation(Meter $elevation): static
    {
        return clone ($this, [
            'elevation' => $elevation,
        ]);
    }

    public function getNumberOfActivities(): int
    {
        return $this->numberOfActivities;
    }

    public function withNumberOfActivities(int $numberOfActivities): static
    {
        return clone ($this, [
            'numberOfActivities' => $numberOfActivities,
        ]);
    }

    public function getTotalCalories(): int
    {
        return $this->totalCalories;
    }

    public function withTotalCalories(int $totalCalories): static
    {
        return clone ($this, [
            'totalCalories' => $totalCalories,
        ]);
    }

    public function getMovingTimeFormatted(): string
    {
        return $this->formatDurationAsHumanString($this->movingTime->toInt());
    }

    public function getMovingTimeInHours(): Hour
    {
        return $this->movingTime->toHour();
    }

    public function getAverageDistance(): Kilometer
    {
        if (0 === $this->numberOfActivities) {
            return Kilometer::zero();
        }

        return Kilometer::from($this->getDistance()->toFloat() / $this->numberOfActivities);
    }

    public function getAverageSpeed(): KmPerHour
    {
        $movingTimeInSeconds = $this->movingTime->toFloat();
        if (0.0 === $movingTimeInSeconds) {
            return KmPerHour::zero();
        }

        return KmPerHour::from(($this->getDistance()->toFloat() / $movingTimeInSeconds) * 3600);
    }

    public function getRelativeCostPerHour(): ?Money
    {
        $movingTimeInHours = $this->getMovingTimeInHours()->toInt();

        return $this->getPurchasePrice()?->divide($movingTimeInHours > 0 ? $movingTimeInHours : 1);
    }

    public function getRelativeCostPerWorkout(): ?Money
    {
        return $this->getPurchasePrice()?->divide($this->numberOfActivities > 0 ? $this->numberOfActivities : 1);
    }

    public function getRelativeCostPerDistanceUnit(UnitSystem $unitSystem): ?Money
    {
        $distance = $this->getDistance()->toUnitSystem($unitSystem)->toInt();

        return $this->getPurchasePrice()?->divide($distance > 0 ? $distance : 1);
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
