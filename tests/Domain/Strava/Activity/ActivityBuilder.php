<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Gear\GearId;
use App\Infrastructure\Geocoding\Nominatim\Location;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ActivityBuilder
{
    private ActivityId $activityId;
    private SerializableDateTime $startDateTime;
    private SportType $sportType;
    private string $name;
    private readonly string $description;
    private readonly Kilometer $distance;
    private readonly Meter $elevation;
    private ?Coordinate $startingCoordinate;
    private readonly int $calories;
    private ?int $averagePower;
    private readonly ?int $maxPower;
    private readonly KmPerHour $averageSpeed;
    private readonly KmPerHour $maxSpeed;
    private ?int $averageHeartRate;
    private readonly ?int $maxHeartRate;
    private readonly ?int $averageCadence;
    private int $movingTimeInSeconds;
    private int $kudoCount;
    private int $totalImageCount;
    private ?string $deviceName;
    /** @var array<string> */
    private readonly array $localImagePaths;
    private ?string $polyline;
    private ?Location $location;
    private readonly string $weather;
    private ?GearId $gearId;
    private readonly ?string $gearName;
    private readonly bool $isCommute;

    private function __construct()
    {
        $this->activityId = ActivityId::fromUnprefixed('903645');
        $this->startDateTime = SerializableDateTime::fromString('2023-10-10');
        $this->sportType = SportType::RIDE;
        $this->kudoCount = 1;
        $this->name = 'Test activity';
        $this->description = '';
        $this->distance = Kilometer::from(10);
        $this->elevation = Meter::from(0);
        $this->startingCoordinate = null;
        $this->calories = 0;
        $this->averagePower = null;
        $this->maxPower = null;
        $this->averageSpeed = KmPerHour::from(0);
        $this->maxSpeed = KmPerHour::from(0);
        $this->averageHeartRate = null;
        $this->maxHeartRate = null;
        $this->averageCadence = null;
        $this->movingTimeInSeconds = 10;
        $this->totalImageCount = 0;
        $this->deviceName = null;
        $this->localImagePaths = [];
        $this->polyline = null;
        $this->weather = '';
        $this->gearId = null;
        $this->location = null;
        $this->gearName = null;
        $this->isCommute = false;
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): Activity
    {
        return Activity::fromState(
            activityId: $this->activityId,
            startDateTime: $this->startDateTime,
            sportType: $this->sportType,
            name: $this->name,
            description: $this->description,
            distance: $this->distance,
            elevation: $this->elevation,
            startingCoordinate: $this->startingCoordinate,
            calories: $this->calories,
            averagePower: $this->averagePower,
            maxPower: $this->maxPower,
            averageSpeed: $this->averageSpeed,
            maxSpeed: $this->maxSpeed,
            averageHeartRate: $this->averageHeartRate,
            maxHeartRate: $this->maxHeartRate,
            averageCadence: $this->averageCadence,
            movingTimeInSeconds: $this->movingTimeInSeconds,
            kudoCount: $this->kudoCount,
            deviceName: $this->deviceName,
            totalImageCount: $this->totalImageCount,
            localImagePaths: $this->localImagePaths,
            polyline: $this->polyline,
            location: $this->location,
            weather: $this->weather,
            gearId: $this->gearId,
            gearName: $this->gearName,
            isCommute: $this->isCommute,
        );
    }

    public function withActivityId(ActivityId $activityId): self
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withKudoCount(int $kudoCount): self
    {
        $this->kudoCount = $kudoCount;

        return $this;
    }

    public function withStartDateTime(SerializableDateTime $startDateTime): self
    {
        $this->startDateTime = $startDateTime;

        return $this;
    }

    public function withAveragePower(int $averagePower): self
    {
        $this->averagePower = $averagePower;

        return $this;
    }

    public function withMovingTimeInSeconds(int $movingTimeInSeconds): self
    {
        $this->movingTimeInSeconds = $movingTimeInSeconds;

        return $this;
    }

    public function withAverageHeartRate(int $averageHeartRate): self
    {
        $this->averageHeartRate = $averageHeartRate;

        return $this;
    }

    public function withGearId(GearId $gearId): self
    {
        $this->gearId = $gearId;

        return $this;
    }

    public function withLocation(?Location $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function withStartingCoordinate(Coordinate $coordinate): self
    {
        $this->startingCoordinate = $coordinate;

        return $this;
    }

    public function withDeviceName(string $deviceName): self
    {
        $this->deviceName = $deviceName;

        return $this;
    }

    public function withSportType(SportType $sportType): self
    {
        $this->sportType = $sportType;

        return $this;
    }

    public function withPolyline(?string $polyline): self
    {
        $this->polyline = $polyline;

        return $this;
    }

    public function withTotalImageCount(int $totalImageCount): self
    {
        $this->totalImageCount = $totalImageCount;

        return $this;
    }
}
