<?php

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\Stream\PowerOutput;
use App\Domain\Strava\Activity\Stream\PowerOutputs;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\LeafletMap;
use App\Domain\Weather\OpenMeteo\Weather;
use App\Infrastructure\Eventing\RecordsEvents;
use App\Infrastructure\Geocoding\Nominatim\Location;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\SerializableTimezone;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'Activity_startDateTimeIndex', columns: ['startDateTime'])]
final class Activity
{
    use RecordsEvents;
    use ProvideTimeFormats;

    public const string DATE_TIME_FORMAT = 'Y-m-d\TH:i:s\Z';

    private ?int $maxCadence = null;
    private ?PowerOutputs $bestPowerOutputs = null;
    /** @var string[] */
    private array $maintenanceTags = [];

    #[ORM\Column(type: 'json', nullable: true)]
    // @phpstan-ignore-next-line
    private readonly array $data;
    #[ORM\Column(type: 'boolean', nullable: true)]
    // @phpstan-ignore-next-line
    private readonly bool $streamsAreImported;

    /**
     * @param array<string> $localImagePaths
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly ActivityId $activityId,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $startDateTime,
        #[ORM\Column(type: 'string')]
        private readonly SportType $sportType,
        #[ORM\Column(type: 'string')]
        private string $name,
        #[ORM\Column(type: 'string', nullable: true)]
        private readonly ?string $description,
        #[ORM\Column(type: 'integer')]
        private Kilometer $distance,
        #[ORM\Column(type: 'integer')]
        private Meter $elevation,
        #[ORM\Embedded(class: Coordinate::class)]
        private ?Coordinate $startingCoordinate,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $calories,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $averagePower,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $maxPower,
        #[ORM\Column(type: 'float')]
        private KmPerHour $averageSpeed,
        #[ORM\Column(type: 'float')]
        private KmPerHour $maxSpeed,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $averageHeartRate,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $maxHeartRate,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $averageCadence,
        #[ORM\Column(type: 'integer')]
        private int $movingTimeInSeconds,
        #[ORM\Column(type: 'integer')]
        private int $kudoCount,
        #[ORM\Column(type: 'string', nullable: true)]
        private readonly ?string $deviceName,
        #[ORM\Column(type: 'integer')]
        private int $totalImageCount,
        #[ORM\Column(type: 'text', nullable: true)]
        private array $localImagePaths,
        #[ORM\Column(type: 'text', nullable: true)]
        private ?string $polyline,
        #[ORM\Column(type: 'json', nullable: true)]
        private ?Location $location,
        #[ORM\Column(type: 'json', nullable: true)]
        private ?string $weather,
        #[ORM\Column(type: 'string', nullable: true)]
        private ?GearId $gearId,
        #[ORM\Column(type: 'string', nullable: true)]
        private ?string $gearName,
        #[ORM\Column(type: 'boolean', nullable: true)]
        private bool $isCommute,
        #[ORM\Column(type: 'string', nullable: true)]
        private ?WorkoutType $workoutType,
    ) {
    }

    /**
     * @param array<mixed> $rawData
     */
    public static function createFromRawData(
        array $rawData,
        ?GearId $gearId,
        ?string $gearName,
    ): self {
        $startDate = SerializableDateTime::createFromFormat(
            format: Activity::DATE_TIME_FORMAT,
            datetime: $rawData['start_date_local'],
            timezone: SerializableTimezone::default(),
        );

        return self::fromState(
            activityId: ActivityId::fromUnprefixed((string) $rawData['id']),
            startDateTime: $startDate,
            sportType: SportType::from($rawData['sport_type']),
            name: $rawData['name'],
            description: $rawData['description'],
            distance: Kilometer::from(round($rawData['distance'] / 1000, 3)),
            elevation: Meter::from(round($rawData['total_elevation_gain'])),
            startingCoordinate: Coordinate::createFromOptionalLatAndLng(
                Latitude::fromOptionalString($rawData['start_latlng'][0] ?? null),
                Longitude::fromOptionalString($rawData['start_latlng'][1] ?? null),
            ),
            calories: (int) ($rawData['calories'] ?? 0),
            averagePower: isset($rawData['average_watts']) ? (int) $rawData['average_watts'] : null,
            maxPower: isset($rawData['max_watts']) ? (int) $rawData['max_watts'] : null,
            averageSpeed: MetersPerSecond::from($rawData['average_speed'])->toKmPerHour(),
            maxSpeed: MetersPerSecond::from($rawData['max_speed'])->toKmPerHour(),
            averageHeartRate: isset($rawData['average_heartrate']) ? (int) round($rawData['average_heartrate']) : null,
            maxHeartRate: isset($rawData['max_heartrate']) ? (int) round($rawData['max_heartrate']) : null,
            averageCadence: isset($rawData['average_cadence']) ? (int) round($rawData['average_cadence']) : null,
            movingTimeInSeconds: $rawData['moving_time'] ?? 0,
            kudoCount: $rawData['kudos_count'] ?? 0,
            deviceName: $rawData['device_name'] ?? null,
            totalImageCount: $rawData['total_photo_count'] ?? 0,
            localImagePaths: [],
            polyline: $rawData['map']['summary_polyline'] ?? null,
            location: null,
            weather: null,
            gearId: $gearId,
            gearName: $gearName,
            isCommute: $rawData['commute'] ?? false,
            workoutType: WorkoutType::fromStravaInt($rawData['workout_type'] ?? null),
        );
    }

    /**
     * @param array<string> $localImagePaths
     */
    public static function fromState(
        ActivityId $activityId,
        SerializableDateTime $startDateTime,
        SportType $sportType,
        string $name,
        ?string $description,
        Kilometer $distance,
        Meter $elevation,
        ?Coordinate $startingCoordinate,
        ?int $calories,
        ?int $averagePower,
        ?int $maxPower,
        KmPerHour $averageSpeed,
        KmPerHour $maxSpeed,
        ?int $averageHeartRate,
        ?int $maxHeartRate,
        ?int $averageCadence,
        int $movingTimeInSeconds,
        int $kudoCount,
        ?string $deviceName,
        int $totalImageCount,
        array $localImagePaths,
        ?string $polyline,
        ?Location $location,
        ?string $weather,
        ?GearId $gearId,
        ?string $gearName,
        bool $isCommute,
        ?WorkoutType $workoutType,
    ): self {
        return new self(
            activityId: $activityId,
            startDateTime: $startDateTime,
            sportType: $sportType,
            name: $name,
            description: $description,
            distance: $distance,
            elevation: $elevation,
            startingCoordinate: $startingCoordinate,
            calories: $calories,
            averagePower: $averagePower,
            maxPower: $maxPower,
            averageSpeed: $averageSpeed,
            maxSpeed: $maxSpeed,
            averageHeartRate: $averageHeartRate,
            maxHeartRate: $maxHeartRate,
            averageCadence: $averageCadence,
            movingTimeInSeconds: $movingTimeInSeconds,
            kudoCount: $kudoCount,
            deviceName: $deviceName,
            totalImageCount: $totalImageCount,
            localImagePaths: $localImagePaths,
            polyline: $polyline,
            location: $location,
            weather: $weather,
            gearId: $gearId,
            gearName: $gearName,
            isCommute: $isCommute,
            workoutType: $workoutType
        );
    }

    public function getId(): ActivityId
    {
        return $this->activityId;
    }

    public function getStartDate(): SerializableDateTime
    {
        return $this->startDateTime;
    }

    public function getSportType(): SportType
    {
        return $this->sportType;
    }

    public function getStartingCoordinate(): ?Coordinate
    {
        return $this->startingCoordinate;
    }

    public function updateStartingCoordinate(?Coordinate $coordinate): self
    {
        $this->startingCoordinate = $coordinate;

        return $this;
    }

    public function getKudoCount(): int
    {
        return $this->kudoCount;
    }

    public function updateKudoCount(int $count): self
    {
        $this->kudoCount = $count;

        return $this;
    }

    public function getGearId(): ?GearId
    {
        return $this->gearId;
    }

    public function updateGear(
        ?GearId $gearId = null,
        ?string $gearName = null,
    ): self {
        $this->gearId = $gearId;
        $this->gearName = $gearName;

        return $this;
    }

    public function getGearName(): ?string
    {
        return $this->gearName;
    }

    public function hasDetailedPowerData(): bool
    {
        if (is_null($this->bestPowerOutputs)) {
            return false;
        }

        return !$this->bestPowerOutputs->isEmpty();
    }

    public function getBestAveragePowerForTimeInterval(int $timeInterval): ?PowerOutput
    {
        if (is_null($this->bestPowerOutputs)) {
            return null;
        }

        return $this->bestPowerOutputs->find(fn (PowerOutput $bestPowerOutput) => $bestPowerOutput->getTimeIntervalInSeconds() === $timeInterval);
    }

    public function enrichWithBestPowerOutputs(PowerOutputs $bestPowerOutputs): void
    {
        $this->bestPowerOutputs = $bestPowerOutputs;
    }

    public function getWeather(): ?Weather
    {
        if (!$this->weather) {
            return null;
        }
        if ($decodedWeather = Json::decode($this->weather)) {
            return Weather::fromState($decodedWeather);
        }

        return null;
    }

    public function updateWeather(?Weather $weather): void
    {
        $this->weather = Json::encode($weather);
    }

    /**
     * @return array<string>
     */
    public function getLocalImagePaths(): array
    {
        return array_map(
            fn (string $path) => str_starts_with($path, '/') ? $path : '/'.$path,
            $this->localImagePaths
        );
    }

    /**
     * @param array<string> $localImagePaths
     */
    public function updateLocalImagePaths(array $localImagePaths): void
    {
        $this->localImagePaths = $localImagePaths;
        $this->totalImageCount = count($this->localImagePaths);
    }

    public function getTotalImageCount(): int
    {
        return $this->totalImageCount;
    }

    public function getOriginalName(): string
    {
        return trim(str_replace('Zwift - ', '', $this->name));
    }

    public function getName(): string
    {
        if (empty($this->maintenanceTags)) {
            return $this->getOriginalName();
        }

        return trim(str_replace($this->maintenanceTags, '', $this->getOriginalName()));
    }

    public function getSanitizedName(): string
    {
        return htmlspecialchars($this->getName());
    }

    public function updateName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return trim($this->description ?? '');
    }

    public function getDistance(): Kilometer
    {
        return $this->distance;
    }

    public function updateDistance(Kilometer $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getElevation(): Meter
    {
        return $this->elevation;
    }

    public function updateElevation(Meter $elevation): self
    {
        $this->elevation = $elevation;

        return $this;
    }

    public function getCalories(): ?int
    {
        return $this->calories;
    }

    public function getAveragePower(): ?int
    {
        return $this->averagePower;
    }

    public function getMaxPower(): ?int
    {
        return $this->maxPower;
    }

    public function getAverageSpeed(): KmPerHour
    {
        return $this->averageSpeed;
    }

    public function updateAverageSpeed(KmPerHour $averageSpeed): self
    {
        $this->averageSpeed = $averageSpeed;

        return $this;
    }

    public function getPaceInSecPerKm(): SecPerKm
    {
        return $this->getAverageSpeed()->toMetersPerSecond()->toSecPerKm();
    }

    public function getMaxSpeed(): KmPerHour
    {
        return $this->maxSpeed;
    }

    public function updateMaxSpeed(KmPerHour $maxSpeed): self
    {
        $this->maxSpeed = $maxSpeed;

        return $this;
    }

    public function getAverageHeartRate(): ?int
    {
        return $this->averageHeartRate;
    }

    public function getMaxHeartRate(): ?int
    {
        return $this->maxHeartRate;
    }

    public function getAverageCadence(): ?int
    {
        return $this->averageCadence;
    }

    public function getMaxCadence(): ?int
    {
        return $this->maxCadence;
    }

    public function enrichWithMaxCadence(int $maxCadence): void
    {
        $this->maxCadence = $maxCadence;
    }

    public function getMovingTimeInSeconds(): int
    {
        return $this->movingTimeInSeconds;
    }

    public function getMovingTimeInHours(): float
    {
        return round($this->movingTimeInSeconds / 3600, 1);
    }

    public function updateMovingTimeInSeconds(int $movingTimeInSeconds): self
    {
        $this->movingTimeInSeconds = $movingTimeInSeconds;

        return $this;
    }

    public function getMovingTimeFormatted(): string
    {
        return $this->formatDurationForHumans($this->getMovingTimeInSeconds());
    }

    public function getUrl(): string
    {
        return 'https://www.strava.com/activities/'.$this->getId()->toUnprefixedString();
    }

    public function getPolyline(): ?string
    {
        return $this->polyline;
    }

    public function updatePolyline(?string $polyline): self
    {
        $this->polyline = $polyline;

        return $this;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function isCommute(): bool
    {
        return $this->isCommute;
    }

    public function updateCommute(bool $isCommute): self
    {
        $this->isCommute = $isCommute;

        return $this;
    }

    public function getWorkoutType(): ?WorkoutType
    {
        return $this->workoutType;
    }

    public function updateWorkoutType(?WorkoutType $workoutType): self
    {
        $this->workoutType = $workoutType;

        return $this;
    }

    public function getCarbonSaved(): Kilogram
    {
        if (!$this->isCommute) {
            return Kilogram::zero();
        }

        return Kilogram::from($this->getDistance()->toFloat() * 0.2178);
    }

    public function isZwiftRide(): bool
    {
        return 'zwift' === strtolower($this->getDeviceName() ?? '');
    }

    public function isRouvyRide(): bool
    {
        return 'rouvy' === strtolower($this->getDeviceName() ?? '');
    }

    public function getLeafletMap(): ?LeafletMap
    {
        if (!$this->getPolyline()) {
            return null;
        }
        if (!$this->isZwiftRide()) {
            return LeafletMap::REAL_WORLD;
        }
        if (!$startingCoordinate = $this->getStartingCoordinate()) {
            return null;
        }

        return LeafletMap::forZwiftStartingCoordinate($startingCoordinate);
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function updateLocation(?Location $location = null): self
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @param string[] $tags
     */
    public function enrichWithMaintenanceTags(array $tags): void
    {
        $this->maintenanceTags = $tags;
    }

    /**
     * @return string[]
     */
    public function getSearchables(): array
    {
        return array_map('strtolower', [$this->getName()]);
    }

    /**
     * @return array<string, string|int>
     */
    public function getFilterables(): array
    {
        return array_filter([
            'sportType' => $this->getSportType()->value,
            'start-date' => $this->getStartDate()->getTimestamp() * 1000, // JS timestamp is in milliseconds,
            'countryCode' => $this->getLocation()?->getCountryCode(),
            'isCommute' => $this->isCommute() ? 'true' : 'false',
            'gear' => $this->getGearId() ?? 'gear-none',
            'workoutType' => $this->getWorkoutType()?->value,
        ]);
    }

    /**
     * @return array<string, string|int|float>
     */
    public function getSortables(): array
    {
        return array_filter([
            'start-date' => $this->getStartDate()->getTimestamp(),
            'distance' => round($this->getDistance()->toFloat(), 2),
            'elevation' => $this->getElevation()->toFloat(),
            'moving-time' => $this->getMovingTimeInSeconds(),
            'power' => $this->getAveragePower(),
            'speed' => round($this->getAverageSpeed()->toFloat(), 1),
            'heart-rate' => $this->getAverageHeartRate(),
            'calories' => $this->getCalories(),
        ]);
    }

    /**
     * @return array<string, string|int|float>
     */
    public function getSummables(UnitSystem $unitSystem): array
    {
        return [
            'distance' => round($this->getDistance()->toUnitSystem($unitSystem)->toFloat(), 2),
            'elevation' => $this->getElevation()->toUnitSystem($unitSystem)->toFloat(),
            'moving-time' => $this->getMovingTimeInSeconds() / 3600,
            'calories' => $this->getCalories() ?? 0,
        ];
    }

    public function delete(): void
    {
        $this->recordThat(new ActivityWasDeleted($this->getId()));
    }
}
