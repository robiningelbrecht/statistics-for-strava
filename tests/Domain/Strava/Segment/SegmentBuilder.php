<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Segment;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentId;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\String\Name;

final class SegmentBuilder
{
    private SegmentId $segmentId;
    private Name $name;
    private SportType $sportType;
    private Kilometer $distance;
    private float $maxGradient;
    private bool $isFavourite;
    private ?string $deviceName;
    private ?int $climbCategory;
    private ?string $countryCode;

    private function __construct()
    {
        $this->segmentId = SegmentId::fromUnprefixed('1');
        $this->name = Name::fromString('Segment');
        $this->sportType = SportType::RIDE;
        $this->distance = Kilometer::from(1);
        $this->maxGradient = 5.3;
        $this->isFavourite = false;
        $this->deviceName = 'Polar';
        $this->climbCategory = null;
        $this->countryCode = 'BE';
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): Segment
    {
        return Segment::fromState(
            segmentId: $this->segmentId,
            name: $this->name,
            sportType: $this->sportType,
            distance: $this->distance,
            maxGradient: $this->maxGradient,
            isFavourite: $this->isFavourite,
            climbCategory: $this->climbCategory,
            deviceName: $this->deviceName,
            countryCode: $this->countryCode
        );
    }

    public function withSegmentId(SegmentId $id): self
    {
        $this->segmentId = $id;

        return $this;
    }

    public function withName(Name $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withSportType(SportType $sportType): self
    {
        $this->sportType = $sportType;

        return $this;
    }

    public function withDistance(Kilometer $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function withMaxGradient(float $maxGradient): self
    {
        $this->maxGradient = $maxGradient;

        return $this;
    }

    public function withIsFavourite(bool $isFavourite): self
    {
        $this->isFavourite = $isFavourite;

        return $this;
    }

    public function withDeviceName(string $deviceName): self
    {
        $this->deviceName = $deviceName;

        return $this;
    }

}
