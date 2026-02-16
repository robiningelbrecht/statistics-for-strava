<?php

namespace App\Domain\Activity;

use App\Domain\Activity\Route\RouteGeography;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\ActivityPowerRepository;
use App\Domain\Activity\Stream\PowerOutput;
use App\Domain\Activity\Stream\PowerOutputs;
use App\Domain\Gear\GearId;
use App\Domain\Integration\AI\SupportsAITooling;
use App\Domain\Integration\Weather\OpenMeteo\Weather;
use App\Domain\Zwift\CouldNotDetermineZwiftMap;
use App\Domain\Zwift\ZwiftMap;
use App\Infrastructure\Eventing\RecordsEvents;
use App\Infrastructure\Serialization\Escape;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPer100Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\SerializableTimezone;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'Activity_startDateTimeIndex', columns: ['startDateTime'])]
#[ORM\Index(name: 'Activity_sportType', columns: ['sportType'])]
#[ORM\Index(name: 'Activity_gearId', columns: ['gearId'])]
#[ORM\Index(name: 'Activity_markedForDeletion', columns: ['markedForDeletion'])]
#[ORM\Index(name: 'Activity_streamsAreImported', columns: ['streamsAreImported'])]
final class Activity implements SupportsAITooling
{
    use RecordsEvents;
    use ProvideTimeFormats;

    public const string DATE_TIME_FORMAT = 'Y-m-d\TH:i:s\Z';

    private ?int $maxCadence = null;
    private ?PowerOutputs $bestPowerOutputs = null;
    private ?int $normalizedPower = null;
    private ?string $gearName = null;
    /** @var string[] */
    private array $tags = [];

    #[ORM\Column(type: 'string', nullable: true)]
    // @phpstan-ignore-next-line
    private ActivityType $activityType;
    #[ORM\Column(type: 'json', nullable: true)]
    // @phpstan-ignore-next-line
    private readonly array $data;
    #[ORM\Column(type: 'boolean', nullable: true)]
    // @phpstan-ignore-next-line
    private readonly bool $streamsAreImported;
    #[ORM\Column(type: 'boolean', nullable: true)]
    // @phpstan-ignore-next-line
    private readonly bool $markedForDeletion;

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
        #[ORM\Column(type: 'string', nullable: true)]
        private readonly WorldType $worldType,
        #[ORM\Column(type: 'string')]
        private readonly string $name,
        #[ORM\Column(type: 'string', nullable: true)]
        private readonly ?string $description,
        #[ORM\Column(type: 'integer')]
        private readonly Kilometer $distance,
        #[ORM\Column(type: 'integer')]
        private readonly Meter $elevation,
        #[ORM\Embedded(class: Coordinate::class)]
        private readonly ?Coordinate $startingCoordinate,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $calories,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $averagePower,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $maxPower,
        #[ORM\Column(type: 'float')]
        private readonly KmPerHour $averageSpeed,
        #[ORM\Column(type: 'float')]
        private readonly KmPerHour $maxSpeed,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $averageHeartRate,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $maxHeartRate,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $averageCadence,
        #[ORM\Column(type: 'integer')]
        private readonly int $movingTimeInSeconds,
        #[ORM\Column(type: 'integer')]
        private readonly int $kudoCount,
        #[ORM\Column(type: 'string', nullable: true)]
        private readonly ?string $deviceName,
        #[ORM\Column(type: 'integer')]
        private readonly int $totalImageCount,
        #[ORM\Column(type: 'text', nullable: true)]
        private readonly array $localImagePaths,
        #[ORM\Column(type: 'text', nullable: true)]
        private readonly ?string $polyline,
        #[ORM\Column(type: 'json', nullable: true)]
        private readonly RouteGeography $routeGeography,
        #[ORM\Column(type: 'json', nullable: true)]
        private readonly ?string $weather,
        #[ORM\Column(type: 'string', nullable: true)]
        private readonly ?GearId $gearId,
        #[ORM\Column(type: 'boolean', nullable: true)]
        private readonly bool $isCommute,
        #[ORM\Column(type: 'string', nullable: true)]
        private readonly ?WorkoutType $workoutType,
    ) {
    }

    /**
     * @param array<mixed> $rawData
     */
    public static function createFromRawData(array $rawData): self
    {
        $startDate = SerializableDateTime::createFromFormat(
            format: Activity::DATE_TIME_FORMAT,
            datetime: $rawData['start_date_local'],
            timezone: SerializableTimezone::default(),
        );

        $deviceName = $rawData['device_name'] ?? null;
        $worldType = match (true) {
            'zwift' === strtolower($deviceName ?? '') => WorldType::ZWIFT,
            'rouvy' === strtolower($deviceName ?? '') => WorldType::ROUVY,
            'mywhoosh' === strtolower($deviceName ?? '') => WorldType::MY_WHOOSH,
            str_contains(strtolower($rawData['name'] ?? ''), 'mywhoosh') => WorldType::MY_WHOOSH,
            default => WorldType::REAL_WORLD,
        };

        return self::fromState(
            activityId: ActivityId::fromUnprefixed((string) $rawData['id']),
            startDateTime: $startDate,
            sportType: SportType::from($rawData['sport_type']),
            worldType: $worldType,
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
            deviceName: $deviceName,
            totalImageCount: $rawData['total_photo_count'] ?? 0,
            localImagePaths: [],
            polyline: $rawData['map']['summary_polyline'] ?? null,
            routeGeography: RouteGeography::create([]),
            weather: null,
            gearId: GearId::fromOptionalUnprefixed($rawData['gear_id'] ?? null),
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
        WorldType $worldType,
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
        RouteGeography $routeGeography,
        ?string $weather,
        ?GearId $gearId,
        bool $isCommute,
        ?WorkoutType $workoutType,
    ): self {
        return new self(
            activityId: $activityId,
            startDateTime: $startDateTime,
            sportType: $sportType,
            worldType: $worldType,
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
            routeGeography: $routeGeography,
            weather: $weather,
            gearId: $gearId,
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

    public function getWorldType(): WorldType
    {
        return $this->worldType;
    }

    public function withSportType(SportType $sportType): self
    {
        return clone ($this, [
            'sportType' => $sportType,
        ]);
    }

    public function getStartingCoordinate(): ?Coordinate
    {
        return $this->startingCoordinate;
    }

    public function withStartingCoordinate(?Coordinate $coordinate): self
    {
        return clone ($this, [
            'startingCoordinate' => $coordinate,
        ]);
    }

    public function getKudoCount(): int
    {
        return $this->kudoCount;
    }

    public function withKudoCount(int $count): self
    {
        return clone ($this, [
            'kudoCount' => $count,
        ]);
    }

    public function getGearId(): ?GearId
    {
        return $this->gearId;
    }

    public function getGearIdIncludingNone(): GearId
    {
        return $this->getGearId() ?? GearId::none();
    }

    public function withGear(
        ?GearId $gearId = null,
    ): self {
        return clone ($this, [
            'gearId' => $gearId,
        ]);
    }

    public function withEmptyGear(): self
    {
        return $this->withGear();
    }

    public function getGearName(): ?string
    {
        return $this->gearName;
    }

    public function withGearName(?string $gearName): self
    {
        return clone ($this, [
            'gearName' => $gearName,
        ]);
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

        return $this->bestPowerOutputs->find(fn (PowerOutput $bestPowerOutput): bool => $bestPowerOutput->getTimeIntervalInSeconds() === $timeInterval);
    }

    public function withBestPowerOutputs(PowerOutputs $bestPowerOutputs): self
    {
        if ($bestPowerOutputs->isEmpty()) {
            return $this;
        }

        return clone ($this, [
            'bestPowerOutputs' => $bestPowerOutputs,
        ]);
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

    public function withWeather(?Weather $weather): self
    {
        return clone ($this, [
            'weather' => Json::encode($weather),
        ]);
    }

    /**
     * @return array<string>
     */
    public function getLocalImagePaths(): array
    {
        return array_map(
            fn (string $path): string => str_starts_with($path, '/') ? $path : '/'.$path,
            $this->localImagePaths
        );
    }

    /**
     * @param array<string> $localImagePaths
     */
    public function withLocalImagePaths(array $localImagePaths): self
    {
        return clone ($this, [
            'localImagePaths' => $localImagePaths,
            'totalImageCount' => count($localImagePaths),
        ]);
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
        if ([] === $this->tags) {
            return $this->getOriginalName();
        }

        return trim(str_replace($this->tags, '', $this->getOriginalName()));
    }

    public function getSanitizedName(): string
    {
        return Escape::forJsonEncode($this->getName());
    }

    public function withName(string $name): self
    {
        return clone ($this, [
            'name' => $name,
        ]);
    }

    public function getDescription(): string
    {
        return trim($this->description ?? '');
    }

    public function getDistance(): Kilometer
    {
        return $this->distance;
    }

    public function withDistance(Kilometer $distance): self
    {
        return clone ($this, [
            'distance' => $distance,
        ]);
    }

    public function getElevation(): Meter
    {
        return $this->elevation;
    }

    public function withElevation(Meter $elevation): self
    {
        return clone ($this, [
            'elevation' => $elevation,
        ]);
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

    public function withAverageSpeed(KmPerHour $averageSpeed): self
    {
        return clone ($this, [
            'averageSpeed' => $averageSpeed,
        ]);
    }

    public function getPaceInSecPerKm(): SecPerKm
    {
        return $this->getAverageSpeed()->toMetersPerSecond()->toSecPerKm();
    }

    public function getPaceInSecPer100Meter(): SecPer100Meter
    {
        return $this->getAverageSpeed()->toMetersPerSecond()->toSecPerKm()->toSecPer100Meter();
    }

    public function getMaxSpeed(): KmPerHour
    {
        return $this->maxSpeed;
    }

    public function withMaxSpeed(KmPerHour $maxSpeed): self
    {
        return clone ($this, [
            'maxSpeed' => $maxSpeed,
        ]);
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

    public function withMaxCadence(int $maxCadence): self
    {
        return clone ($this, [
            'maxCadence' => $maxCadence,
        ]);
    }

    public function getMovingTimeInSeconds(): int
    {
        return $this->movingTimeInSeconds;
    }

    public function getMovingTimeInHours(): float
    {
        return round($this->movingTimeInSeconds / 3600, 1);
    }

    public function withMovingTimeInSeconds(int $movingTimeInSeconds): self
    {
        return clone ($this, [
            'movingTimeInSeconds' => $movingTimeInSeconds,
        ]);
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

    public function withPolyline(?string $polyline): self
    {
        return clone ($this, [
            'polyline' => $polyline,
        ]);
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function getDeviceId(): string
    {
        return Name::fromString($this->getDeviceName() ?? 'device-none')->kebabCase();
    }

    public function isCommute(): bool
    {
        return $this->isCommute;
    }

    public function withCommute(bool $isCommute): self
    {
        return clone ($this, [
            'isCommute' => $isCommute,
        ]);
    }

    public function getWorkoutType(): ?WorkoutType
    {
        return $this->workoutType;
    }

    public function withWorkoutType(?WorkoutType $workoutType): self
    {
        return clone ($this, [
            'workoutType' => $workoutType,
        ]);
    }

    public function isZwiftRide(): bool
    {
        return WorldType::ZWIFT === $this->getWorldType();
    }

    public function isRouvyRide(): bool
    {
        return WorldType::ROUVY === $this->getWorldType();
    }

    public function isMyWhooshRide(): bool
    {
        return WorldType::MY_WHOOSH === $this->getWorldType();
    }

    public function getLeafletMap(): ?LeafletMap
    {
        if (!$this->getPolyline()) {
            return null;
        }
        if (!$this->isZwiftRide()) {
            return new RealWorldMap();
        }
        if (!($startingCoordinate = $this->getStartingCoordinate()) instanceof Coordinate) {
            return null;
        }

        try {
            return ZwiftMap::forStartingCoordinate($startingCoordinate);
        } catch (CouldNotDetermineZwiftMap) {
            // Very old Zwift activities have routes that we don't have corresponding maps for.
        }

        return null;
    }

    public function getRouteGeography(): RouteGeography
    {
        return $this->routeGeography;
    }

    public function withRouteGeography(RouteGeography $routeGeography): self
    {
        return clone ($this, [
            'routeGeography' => $routeGeography,
        ]);
    }

    public function getNormalizedPower(): ?int
    {
        return $this->normalizedPower;
    }

    public function withNormalizedPower(?int $normalizedPower): self
    {
        return clone ($this, [
            'normalizedPower' => $normalizedPower,
        ]);
    }

    /**
     * @param string[] $tags
     */
    public function withTags(array $tags): self
    {
        return clone ($this, [
            'tags' => $tags,
        ]);
    }

    /**
     * @return string[]
     */
    public function getSearchables(): array
    {
        return array_map(strtolower(...), [$this->getName()]);
    }

    /**
     * @return array<string, string|int|string[]>
     */
    public function getFilterables(UnitSystem $unitSystem): array
    {
        return array_filter([
            'sportType' => $this->getSportType()->value,
            'start-date' => $this->getStartDate()->getTimestamp() * 1000, // JS timestamp is in milliseconds,
            'countryCode' => $this->getRouteGeography()->getPassedThroughCountries(),
            'isCommute' => $this->isCommute() ? 'true' : 'false',
            'gear' => $this->getGearIdIncludingNone(),
            'workoutType' => $this->getWorkoutType()?->value,
            'device' => $this->getDeviceId(),
            'distance' => (int) round($this->getDistance()->toUnitSystem($unitSystem)->toFloat() * 10), // We don't want to filter on float values, but integers instead.
            'elevation' => (int) round($this->getElevation()->toUnitSystem($unitSystem)->toFloat() * 10),
        ]);
    }

    /**
     * @return array<string, string|int|float>
     */
    public function getSortables(): array
    {
        $bestAveragePowerSortables = [];
        foreach (ActivityPowerRepository::TIME_INTERVALS_IN_SECONDS_REDACTED as $interval) {
            if (!($bestAverage = $this->getBestAveragePowerForTimeInterval($interval)) instanceof PowerOutput) {
                continue;
            }

            $bestAveragePowerSortables[sprintf('power-%ss', $interval)] = $bestAverage->getPower();
        }

        return array_filter(array_merge([
            'start-date' => $this->getStartDate()->getTimestamp(),
            'distance' => round($this->getDistance()->toFloat(), 2),
            'elevation' => $this->getElevation()->toFloat(),
            'moving-time' => $this->getMovingTimeInSeconds(),
            'power' => $this->getAveragePower(),
            'speed' => round($this->getAverageSpeed()->toFloat(), 1),
            'heart-rate' => $this->getAverageHeartRate(),
            'calories' => $this->getCalories(),
        ], $bestAveragePowerSortables));
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

    /**
     * @return array<string, mixed>
     */
    public function exportForAITooling(): array
    {
        return [
            'id' => $this->getId()->toUnprefixedString(),
            'startDateTime' => $this->getStartDate(),
            'sportType' => $this->getSportType()->value,
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'distanceInKilometer' => $this->getDistance(),
            'elevationInMeter' => $this->getElevation(),
            'startingCoordinate' => $this->getStartingCoordinate(),
            'caloriesBurnt' => $this->getCalories(),
            'averagePowerInWatts' => $this->getAveragePower(),
            'maxPowerInWatts' => $this->getMaxPower(),
            'averageSpeed' => $this->getAverageSpeed(),
            'maxSpeed' => $this->getMaxSpeed(),
            'averageHeartRate' => $this->getAverageHeartRate(),
            'maxHeartRate' => $this->getMaxHeartRate(),
            'averageCadence' => $this->getAverageCadence(),
            'movingTimeInSeconds' => $this->getMovingTimeInSeconds(),
            'kudoCount' => $this->getKudoCount(),
            'recordedOnDevice' => $this->getDeviceName(),
            'totalImageCount' => $this->getTotalImageCount(),
            'routeGeography' => $this->getRouteGeography()->jsonSerialize(),
            'weather' => $this->getWeather(),
            'gearId' => $this->getGearId()?->toUnprefixedString(),
            'gearName' => $this->getGearName(),
            'isCommute' => $this->isCommute(),
            'workoutType' => $this->getWorkoutType()?->value,
        ];
    }
}
