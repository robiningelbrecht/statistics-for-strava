<?php

declare(strict_types=1);

namespace App\Tests\Domain\Segment;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Segment\Segment;
use App\Domain\Segment\SegmentId;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\EncodedPolyline;
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
    private readonly ?int $climbCategory;
    private readonly ?string $countryCode;
    private bool $detailsHaveBeenImported;
    private readonly ?EncodedPolyline $polyline;
    private readonly ?Coordinate $startingCoordinate;

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
        $this->detailsHaveBeenImported = false;
        $this->polyline = null;
        $this->startingCoordinate = null;
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
            countryCode: $this->countryCode,
            detailsHaveBeenImported: $this->detailsHaveBeenImported,
            polyline: $this->polyline,
            startingCoordinate: $this->startingCoordinate
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

    public function withDetailsHaveBeenImported(bool $flag): self
    {
        $this->detailsHaveBeenImported = $flag;

        return $this;
    }
}
