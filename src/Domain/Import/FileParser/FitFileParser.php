<?php

declare(strict_types=1);

namespace App\Domain\Import\FileParser;

use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ImportSource;
use App\Domain\Activity\Lap\ActivityLap;
use App\Domain\Activity\Lap\ActivityLapId;
use App\Domain\Activity\Lap\ActivityLaps;
use App\Domain\Activity\Route\RouteGeography;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\ActivityStream;
use App\Domain\Activity\Stream\ActivityStreams;
use App\Domain\Activity\Stream\StreamType;
use App\Domain\Activity\WorldType;
use App\Infrastructure\Process\ProcessFactory;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\EncodedPolyline;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use App\Infrastructure\ValueObject\String\ExternalReferenceId;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class FitFileParser implements ActivityFileParser
{
    // Seconds between the Unix epoch and the FIT epoch (1989-12-31 00:00:00 UTC).
    // FIT timestamps are stored as seconds since the FIT epoch.
    private const int FIT_EPOCH_OFFSET = 631065600;

    // FIT profile Sport enum values.
    private const int SPORT_RUNNING = 1;
    private const int SPORT_CYCLING = 2;
    private const int SPORT_SWIMMING = 5;
    private const int SPORT_WALKING = 11;
    private const int SPORT_ROWING = 15;
    private const int SPORT_HIKING = 17;
    private const int SPORT_E_BIKING = 21;

    // FIT profile SubSport enum values.
    private const int SUB_SPORT_TREADMILL = 1;
    private const int SUB_SPORT_TRAIL = 3;
    private const int SUB_SPORT_SPIN = 5;
    private const int SUB_SPORT_INDOOR_CYCLING = 6;
    private const int SUB_SPORT_MOUNTAIN = 8;
    private const int SUB_SPORT_DOWNHILL = 9;
    private const int SUB_SPORT_CYCLOCROSS = 11;
    private const int SUB_SPORT_E_BIKE_FITNESS = 28;
    private const int SUB_SPORT_INDOOR_RUNNING = 45;
    private const int SUB_SPORT_GRAVEL_CYCLING = 46;
    private const int SUB_SPORT_E_BIKE_MOUNTAIN = 47;
    private const int SUB_SPORT_VIRTUAL_ACTIVITY = 58;

    public function __construct(
        private ProcessFactory $processFactory,
        private Clock $clock,
    ) {
    }

    public function supportedExtension(): string
    {
        return 'fit';
    }

    public function parse(RawActivityFile $file): ParsedActivityFile
    {
        $process = $this->processFactory->create(['fit-tool', (string) $file->getPath()]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new CouldNotParseActivityFile(message: sprintf('fit-tool could not decode "%s": %s', $file->getPath()->getFilename(), trim($process->getErrorOutput())), activityFile: $file);
        }

        try {
            /** @var array<mixed> $decoded */
            $decoded = Json::decode($process->getOutput());
        } catch (\JsonException $exception) {
            throw new CouldNotParseActivityFile(message: sprintf('fit-tool produced invalid JSON for "%s": %s', $file->getPath()->getFilename(), $exception->getMessage()), activityFile: $file);
        }

        /** @var array<int, array<string, mixed>> $messages */
        $messages = $decoded['files'][0]['messages'] ?? [];
        if ([] === $messages) {
            throw new CouldNotParseActivityFile(message: sprintf('No FIT messages found in "%s"', $file->getPath()->getFilename()), activityFile: $file);
        }

        /** @var list<array<string, mixed>> $records */
        $records = [];
        /** @var list<array<string, mixed>> $lapMessages */
        $lapMessages = [];
        /** @var array<string, mixed>|null $session */
        $session = null;
        $deviceName = null;

        foreach ($messages as $message) {
            $fields = $this->fieldMap($message['fields'] ?? []);
            switch ($message['name'] ?? null) {
                case 'record':
                    $records[] = $fields;
                    break;
                case 'lap':
                    $lapMessages[] = $fields;
                    break;
                case 'session':
                    $session ??= $fields;
                    break;
            }
            if (null === $deviceName && is_string($fields['product_name'] ?? null) && '' !== $fields['product_name']) {
                $deviceName = $fields['product_name'];
            }
        }

        if ([] === $records) {
            throw new CouldNotParseActivityFile(message: sprintf('No FIT "record" messages found in "%s"', $file->getPath()->getFilename()), activityFile: $file);
        }

        $session ??= [];

        $startTimestamp = $this->intOrNull($session['start_time'] ?? null)
            ?? $this->intOrNull($records[0]['timestamp'] ?? null);
        if (null === $startTimestamp) {
            throw new CouldNotParseActivityFile(message: sprintf('Could not determine start time in "%s"', $file->getPath()->getFilename()), activityFile: $file);
        }

        $sportType = $this->mapSportType(
            $this->intOrNull($session['sport'] ?? null),
            $this->intOrNull($session['sub_sport'] ?? null),
            $file,
        );

        $streamMap = $this->buildStreams($records, $startTimestamp);
        $activityId = ActivityId::random();
        $work = $this->floatOrNull($session['total_work'] ?? null);

        $activity = Activity::fromState(
            activityId: $activityId,
            startDateTime: SerializableDateTime::fromTimestamp(self::FIT_EPOCH_OFFSET + $startTimestamp),
            sportType: $sportType,
            worldType: WorldType::fromDeviceAndActivityName(
                deviceName: $deviceName,
                activityName: ''
            ),
            importSource: ImportSource::FIT_FILE,
            externalReferenceId: ExternalReferenceId::fromString($file->getPath()->getFilename()),
            name: $file->getPath()->getFilenameWithoutExtension(),
            description: null,
            distance: Kilometer::from(round(($this->floatOrNull($session['total_distance'] ?? null) ?? 0.0) / 1000, 3)),
            elevation: Meter::from(round($this->floatOrNull($session['total_ascent'] ?? null) ?? 0.0)),
            startingCoordinate: $this->resolveStartingCoordinate($session, $streamMap),
            calories: $this->intOrNull($session['total_calories'] ?? null),
            kilojoules: null !== $work ? (int) round($work / 1000) : null,
            averagePower: $this->intOrNull($session['avg_power'] ?? null),
            maxPower: $this->intOrNull($session['max_power'] ?? null),
            averageSpeed: $this->toKmPerHour($this->floatOrNull($session['enhanced_avg_speed'] ?? $session['avg_speed'] ?? null)),
            maxSpeed: $this->toKmPerHour($this->floatOrNull($session['enhanced_max_speed'] ?? $session['max_speed'] ?? null)),
            averageHeartRate: $this->intOrNull($session['avg_heart_rate'] ?? null),
            maxHeartRate: $this->intOrNull($session['max_heart_rate'] ?? null),
            averageCadence: $this->intOrNull($session['avg_cadence'] ?? null),
            movingTimeInSeconds: $this->intOrNull($session['total_timer_time'] ?? null) ?? 0,
            elapsedTimeInSeconds: $this->intOrNull($session['total_elapsed_time'] ?? null) ?? 0,
            kudoCount: 0,
            deviceName: $deviceName,
            totalImageCount: 0,
            localImagePaths: [],
            polyline: $this->encodePolyline($streamMap),
            routeGeography: RouteGeography::create([]),
            weather: null,
            gearId: null,
            isCommute: false,
            workoutType: null,
        );

        return ParsedActivityFile::create(
            activity: $activity,
            streams: $this->buildActivityStreams($streamMap, $activityId),
            laps: $this->buildActivityLaps($this->buildLaps($lapMessages, $startTimestamp), $activityId),
        );
    }

    /**
     * @param array<string, list<mixed>> $streamMap
     */
    private function buildActivityStreams(array $streamMap, ActivityId $activityId): ActivityStreams
    {
        $createdOn = $this->clock->getCurrentDateTimeImmutable();

        $streams = ActivityStreams::empty();
        foreach ($streamMap as $type => $values) {
            if (!$streamType = StreamType::tryFrom($type)) {
                continue;
            }
            if ([] === array_filter($values, static fn (mixed $value): bool => null !== $value)) {
                continue;
            }
            $streams->add(ActivityStream::create(
                activityId: $activityId,
                streamType: $streamType,
                streamData: $values,
                createdOn: $createdOn,
            ));
        }

        return $streams;
    }

    /**
     * @param list<array<string, mixed>> $rawLaps
     */
    private function buildActivityLaps(array $rawLaps, ActivityId $activityId): ActivityLaps
    {
        $averageSpeeds = array_map(static fn (array $lap): float => (float) ($lap['average_speed'] ?? 0.0), $rawLaps);
        $minAverageSpeed = MetersPerSecond::from([] !== $averageSpeeds ? min($averageSpeeds) : 0.0);
        $maxAverageSpeed = MetersPerSecond::from([] !== $averageSpeeds ? max($averageSpeeds) : 0.0);

        $laps = ActivityLaps::empty();
        foreach ($rawLaps as $lap) {
            $laps->add(ActivityLap::create(
                lapId: ActivityLapId::random(),
                activityId: $activityId,
                lapNumber: (int) $lap['lap_index'],
                name: (string) $lap['name'],
                elapsedTimeInSeconds: (int) $lap['elapsed_time'],
                movingTimeInSeconds: (int) $lap['moving_time'],
                distance: Meter::from((float) $lap['distance']),
                averageSpeed: MetersPerSecond::from((float) $lap['average_speed']),
                minAverageSpeed: $minAverageSpeed,
                maxAverageSpeed: $maxAverageSpeed,
                maxSpeed: MetersPerSecond::from((float) $lap['max_speed']),
                elevationDifference: Meter::from((float) ($lap['total_elevation_gain'] ?? 0)),
                averageHeartRate: empty($lap['average_heartrate']) ? null : (int) round((float) $lap['average_heartrate']),
            ));
        }

        return $laps;
    }

    /**
     * @param list<array<string, mixed>> $records
     *
     * @return array<string, list<mixed>>
     */
    private function buildStreams(array $records, int $startTimestamp): array
    {
        $streams = [
            StreamType::TIME->value => [],
            StreamType::DISTANCE->value => [],
            StreamType::LAT_LNG->value => [],
            StreamType::ALTITUDE->value => [],
            StreamType::VELOCITY->value => [],
            StreamType::HEART_RATE->value => [],
            StreamType::CADENCE->value => [],
            StreamType::WATTS->value => [],
            StreamType::TEMP->value => [],
        ];

        foreach ($records as $record) {
            $timestamp = $this->intOrNull($record['timestamp'] ?? null);
            $streams[StreamType::TIME->value][] = null !== $timestamp ? $timestamp - $startTimestamp : null;
            $streams[StreamType::DISTANCE->value][] = $this->floatOrNull($record['distance'] ?? null);

            $latitude = $this->floatOrNull($record['position_lat'] ?? null);
            $longitude = $this->floatOrNull($record['position_long'] ?? null);
            $streams[StreamType::LAT_LNG->value][] = (null !== $latitude && null !== $longitude)
                ? [$this->semicirclesToDegrees($latitude), $this->semicirclesToDegrees($longitude)]
                : null;

            $streams[StreamType::ALTITUDE->value][] = $this->floatOrNull($record['enhanced_altitude'] ?? $record['altitude'] ?? null);
            $streams[StreamType::VELOCITY->value][] = $this->floatOrNull($record['enhanced_speed'] ?? $record['speed'] ?? null);
            $streams[StreamType::HEART_RATE->value][] = $this->intOrNull($record['heart_rate'] ?? null);
            $streams[StreamType::CADENCE->value][] = $this->intOrNull($record['cadence'] ?? null);
            $streams[StreamType::WATTS->value][] = $this->intOrNull($record['power'] ?? null);
            $streams[StreamType::TEMP->value][] = $this->intOrNull($record['temperature'] ?? null);
        }

        return $streams;
    }

    /**
     * @param list<array<string, mixed>> $lapMessages
     *
     * @return list<array<string, mixed>>
     */
    private function buildLaps(array $lapMessages, int $startTimestamp): array
    {
        $laps = [];
        foreach ($lapMessages as $index => $lap) {
            $startTime = $this->intOrNull($lap['start_time'] ?? null) ?? $startTimestamp;
            $laps[] = [
                'id' => $index + 1,
                'lap_index' => $index + 1,
                'name' => sprintf('Lap %d', $index + 1),
                'elapsed_time' => $this->intOrNull($lap['total_elapsed_time'] ?? null) ?? 0,
                'moving_time' => $this->intOrNull($lap['total_timer_time'] ?? null) ?? 0,
                'distance' => $this->floatOrNull($lap['total_distance'] ?? null) ?? 0.0,
                'average_speed' => $this->floatOrNull($lap['enhanced_avg_speed'] ?? $lap['avg_speed'] ?? null) ?? 0.0,
                'max_speed' => $this->floatOrNull($lap['enhanced_max_speed'] ?? $lap['max_speed'] ?? null) ?? 0.0,
                'total_elevation_gain' => $this->floatOrNull($lap['total_ascent'] ?? null) ?? 0.0,
                'average_heartrate' => $this->intOrNull($lap['avg_heart_rate'] ?? null),
                'start_date' => SerializableDateTime::fromTimestamp(self::FIT_EPOCH_OFFSET + $startTime)->format(\DateTimeInterface::ATOM),
            ];
        }

        return $laps;
    }

    /**
     * @param array<string, mixed>       $session
     * @param array<string, list<mixed>> $streams
     */
    private function resolveStartingCoordinate(array $session, array $streams): ?Coordinate
    {
        $latitude = $this->floatOrNull($session['start_position_lat'] ?? null);
        $longitude = $this->floatOrNull($session['start_position_long'] ?? null);
        if (null !== $latitude && null !== $longitude) {
            return Coordinate::createFromLatAndLng(
                Latitude::fromString((string) $this->semicirclesToDegrees($latitude)),
                Longitude::fromString((string) $this->semicirclesToDegrees($longitude)),
            );
        }

        foreach ($streams[StreamType::LAT_LNG->value] ?? [] as $point) {
            if (is_array($point)) {
                return Coordinate::createFromLatAndLng(
                    Latitude::fromString((string) $point[0]),
                    Longitude::fromString((string) $point[1]),
                );
            }
        }

        return null;
    }

    private function mapSportType(?int $sport, ?int $subSport, RawActivityFile $file): SportType
    {
        $sportType = match (true) {
            self::SPORT_RUNNING === $sport && self::SUB_SPORT_TRAIL === $subSport => SportType::TRAIL_RUN,
            self::SPORT_RUNNING === $sport && in_array($subSport, [self::SUB_SPORT_TREADMILL, self::SUB_SPORT_INDOOR_RUNNING, self::SUB_SPORT_VIRTUAL_ACTIVITY], true) => SportType::VIRTUAL_RUN,
            self::SPORT_RUNNING === $sport => SportType::RUN,
            self::SPORT_CYCLING === $sport && in_array($subSport, [self::SUB_SPORT_MOUNTAIN, self::SUB_SPORT_DOWNHILL, self::SUB_SPORT_CYCLOCROSS], true) => SportType::MOUNTAIN_BIKE_RIDE,
            self::SPORT_CYCLING === $sport && self::SUB_SPORT_GRAVEL_CYCLING === $subSport => SportType::GRAVEL_RIDE,
            self::SPORT_CYCLING === $sport && in_array($subSport, [self::SUB_SPORT_INDOOR_CYCLING, self::SUB_SPORT_SPIN, self::SUB_SPORT_VIRTUAL_ACTIVITY], true) => SportType::VIRTUAL_RIDE,
            self::SPORT_CYCLING === $sport && in_array($subSport, [self::SUB_SPORT_E_BIKE_FITNESS, self::SUB_SPORT_E_BIKE_MOUNTAIN], true) => SportType::E_BIKE_RIDE,
            self::SPORT_E_BIKING === $sport => SportType::E_BIKE_RIDE,
            self::SPORT_CYCLING === $sport => SportType::RIDE,
            self::SPORT_WALKING === $sport => SportType::WALK,
            self::SPORT_HIKING === $sport => SportType::HIKE,
            self::SPORT_SWIMMING === $sport => SportType::SWIM,
            self::SPORT_ROWING === $sport => SportType::ROWING,
            default => null,
        };

        if (!$sportType instanceof SportType) {
            throw new CouldNotParseActivityFile(message: sprintf('Unsupported FIT sport %s (sub sport %s)', $sport ?? 'null', $subSport ?? 'null'), activityFile: $file);
        }

        return $sportType;
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     *
     * @return array<string, mixed>
     */
    private function fieldMap(array $fields): array
    {
        $map = [];
        foreach ($fields as $field) {
            if (!is_string($field['name'] ?? null)) {
                continue;
            }
            $map[$field['name']] = $field['value'] ?? null;
        }

        return $map;
    }

    /**
     * @param array<string, list<mixed>> $streamMap
     */
    private function encodePolyline(array $streamMap): ?string
    {
        /** @var array<int, array{float, float}> $coordinates */
        $coordinates = array_values(array_filter(
            $streamMap[StreamType::LAT_LNG->value] ?? [],
            is_array(...),
        ));

        if ([] === $coordinates) {
            return null;
        }

        return (string) EncodedPolyline::encode($coordinates);
    }

    private function toKmPerHour(?float $meterPerSecond): KmPerHour
    {
        if (null === $meterPerSecond) {
            return KmPerHour::zero();
        }

        return MetersPerSecond::from($meterPerSecond)->toKmPerHour();
    }

    private function semicirclesToDegrees(float $semicircles): float
    {
        return $semicircles * 180 / 2 ** 31;
    }

    private function floatOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) round((float) $value) : null;
    }
}
