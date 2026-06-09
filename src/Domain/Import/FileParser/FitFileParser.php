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
use App\Domain\Import\FileParser\Fit\FitManufacturer;
use App\Domain\Import\FileParser\Fit\FitProduct;
use App\Domain\Import\FileParser\Fit\FitSportType;
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
use App\Infrastructure\ValueObject\Time\SerializableTimezone;

final readonly class FitFileParser implements ActivityFileParser
{
    // Seconds between the Unix epoch and the FIT epoch (1989-12-31 00:00:00 UTC).
    // FIT timestamps are stored as seconds since the FIT epoch.
    private const int FIT_EPOCH_OFFSET = 631065600;

    public function __construct(
        private ProcessFactory $processFactory,
        private Clock $clock,
        private ?SerializableTimezone $timezone,
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
        $manufacturerId = null;
        $productId = null;

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
                case 'file_id':
                    $manufacturerId ??= $this->intOrNull($fields['manufacturer'] ?? null);
                    $productId ??= $this->intOrNull($fields['product'] ?? null);
                    break;
            }
            if (null === $deviceName && is_string($fields['product_name'] ?? null) && '' !== $fields['product_name']) {
                $deviceName = $fields['product_name'];
            }
        }

        $deviceName ??= $this->resolveDeviceName($manufacturerId, $productId);

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
            startDateTime: SerializableDateTime::fromTimestamp(self::FIT_EPOCH_OFFSET + $startTimestamp)->toTimezone($this->timezone ?? SerializableTimezone::UTC()),
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
            laps: $this->buildActivityLaps($this->buildLaps($lapMessages), $activityId),
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
    private function buildLaps(array $lapMessages): array
    {
        $laps = [];
        foreach ($lapMessages as $index => $lap) {
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
        // Indoor/virtual activities (e.g. Zwift) leave the session start position
        // at 0/0 ("null island"); fall through to the first GPS record instead.
        if (null !== $latitude && null !== $longitude && (0.0 !== $latitude || 0.0 !== $longitude)) {
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
        $sportType = FitSportType::resolve($sport, $subSport);

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

    private function resolveDeviceName(?int $manufacturerId, ?int $productId): ?string
    {
        if (null === $manufacturerId) {
            return null;
        }

        $product = null !== $productId ? FitProduct::name($manufacturerId, $productId) : null;

        return $product ?? FitManufacturer::name($manufacturerId);
    }
}
