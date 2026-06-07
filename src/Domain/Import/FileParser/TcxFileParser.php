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

final readonly class TcxFileParser implements ActivityFileParser
{
    public function __construct(
        private Clock $clock,
    ) {
    }

    public function supportedExtension(): string
    {
        return 'tcx';
    }

    public function parse(RawActivityFile $file): ParsedActivityFile
    {
        $contents = $file->getContents();
        if ('' === trim($contents)) {
            throw new CouldNotParseActivityFile(message: sprintf('Could not read "%s"', $file->getPath()->getFilename()), activityFile: $file);
        }

        // Strip namespace declarations and prefixes so SimpleXML element access is uniform
        // regardless of the file's (default + ActivityExtension) namespaces.
        $contents = (string) preg_replace('/xmlns(:\w+)?="[^"]*"/', '', $contents);
        $contents = (string) preg_replace('/(<\/?)\w+:/', '$1', $contents);

        $previousErrorState = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contents);
        libxml_use_internal_errors($previousErrorState);

        if (false === $xml) {
            throw new CouldNotParseActivityFile(message: sprintf('"%s" is not valid TCX XML', $file->getPath()->getFilename()), activityFile: $file);
        }

        $activityXml = $xml->Activities->Activity ?? null;
        if (null === $activityXml) {
            throw new CouldNotParseActivityFile(message: sprintf('No <Activity> found in "%s"', $file->getPath()->getFilename()), activityFile: $file);
        }

        $sportType = $this->mapSportType((string) $activityXml['Sport'], $file);
        $deviceName = property_exists($activityXml->Creator, 'Name') && null !== $activityXml->Creator->Name ? (string) $activityXml->Creator->Name : null;

        $startTimestamp = null;
        $streams = [
            StreamType::TIME->value => [],
            StreamType::DISTANCE->value => [],
            StreamType::LAT_LNG->value => [],
            StreamType::ALTITUDE->value => [],
            StreamType::VELOCITY->value => [],
            StreamType::HEART_RATE->value => [],
            StreamType::CADENCE->value => [],
            StreamType::WATTS->value => [],
        ];
        $laps = [];

        foreach ($activityXml->Lap as $lapIndex => $lap) {
            $lapStart = isset($lap['StartTime']) ? SerializableDateTime::fromString((string) $lap['StartTime'])->getTimestamp() : null;

            foreach ($lap->Track->Trackpoint ?? [] as $trackpoint) {
                $time = property_exists($trackpoint, 'Time') && null !== $trackpoint->Time ? SerializableDateTime::fromString((string) $trackpoint->Time)->getTimestamp() : null;
                $startTimestamp ??= $time;

                $streams[StreamType::TIME->value][] = (null !== $time && null !== $startTimestamp) ? $time - $startTimestamp : null;
                $streams[StreamType::DISTANCE->value][] = property_exists($trackpoint, 'DistanceMeters') && null !== $trackpoint->DistanceMeters ? (float) $trackpoint->DistanceMeters : null;
                $streams[StreamType::ALTITUDE->value][] = property_exists($trackpoint, 'AltitudeMeters') && null !== $trackpoint->AltitudeMeters ? (float) $trackpoint->AltitudeMeters : null;

                $latitude = property_exists($trackpoint->Position, 'LatitudeDegrees') && null !== $trackpoint->Position->LatitudeDegrees ? (float) $trackpoint->Position->LatitudeDegrees : null;
                $longitude = property_exists($trackpoint->Position, 'LongitudeDegrees') && null !== $trackpoint->Position->LongitudeDegrees ? (float) $trackpoint->Position->LongitudeDegrees : null;
                $streams[StreamType::LAT_LNG->value][] = (null !== $latitude && null !== $longitude) ? [$latitude, $longitude] : null;

                $streams[StreamType::HEART_RATE->value][] = property_exists($trackpoint->HeartRateBpm, 'Value') && null !== $trackpoint->HeartRateBpm->Value ? (int) $trackpoint->HeartRateBpm->Value : null;
                $streams[StreamType::CADENCE->value][] = property_exists($trackpoint, 'Cadence') && null !== $trackpoint->Cadence ? (int) $trackpoint->Cadence : null;

                $tpx = $this->extensionValues($trackpoint);
                $streams[StreamType::VELOCITY->value][] = isset($tpx['Speed']) ? (float) $tpx['Speed'] : null;
                $streams[StreamType::WATTS->value][] = isset($tpx['Watts']) ? (int) $tpx['Watts'] : null;
            }

            $laps[] = $this->buildLap((int) $lapIndex, $lap, $lapStart);
        }

        if (null === $startTimestamp) {
            throw new CouldNotParseActivityFile(message: sprintf('No trackpoints with a timestamp found in "%s"', $file->getPath()->getFilename()), activityFile: $file);
        }

        $velocities = array_filter($streams[StreamType::VELOCITY->value], static fn (mixed $v): bool => null !== $v);
        $activityId = ActivityId::random();
        $activity = Activity::fromState(
            activityId: $activityId,
            startDateTime: SerializableDateTime::fromTimestamp($startTimestamp),
            sportType: $sportType,
            worldType: WorldType::fromDeviceAndActivityName(
                deviceName: $deviceName,
                activityName: $file->getPath()->getFilename()
            ),
            importSource: ImportSource::TCX_FILE,
            externalReferenceId: ExternalReferenceId::fromString($file->getPath()->getFilename()),
            name: $file->getPath()->getFilenameWithoutExtension(),
            description: null,
            distance: Kilometer::from(round($this->sumLapValues($laps, 'distance') / 1000, 3)),
            elevation: Meter::from(round($this->sumLapValues($laps, 'total_elevation_gain'))),
            startingCoordinate: $this->resolveStartingCoordinate($streams),
            calories: $this->sumCalories($activityXml),
            kilojoules: null,
            averagePower: $this->averageStream($streams[StreamType::WATTS->value]),
            maxPower: $this->maxStream($streams[StreamType::WATTS->value]),
            averageSpeed: $this->toKmPerHour([] !== $velocities ? array_sum($velocities) / count($velocities) : null),
            maxSpeed: $this->toKmPerHour([] !== $velocities ? max($velocities) : null),
            averageHeartRate: $this->averageStream($streams[StreamType::HEART_RATE->value]),
            maxHeartRate: $this->maxStream($streams[StreamType::HEART_RATE->value]),
            averageCadence: $this->averageStream($streams[StreamType::CADENCE->value]),
            movingTimeInSeconds: (int) round($this->sumLapValues($laps, 'moving_time')),
            elapsedTimeInSeconds: (int) round($this->sumLapValues($laps, 'elapsed_time')),
            kudoCount: 0,
            deviceName: $deviceName,
            totalImageCount: 0,
            localImagePaths: [],
            polyline: $this->encodePolyline($streams),
            routeGeography: RouteGeography::create([]),
            weather: null,
            gearId: null,
            isCommute: false,
            workoutType: null,
        );

        return ParsedActivityFile::create(
            activity: $activity,
            streams: $this->buildActivityStreams($streams, $activityId),
            laps: $this->buildActivityLaps($laps, $activityId),
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
            if (!$streamType = StreamType::tryFrom((string) $type)) {
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
     * @return array<string, mixed>
     */
    private function buildLap(int $index, \SimpleXMLElement $lap, ?int $lapStart): array
    {
        return [
            'id' => $index + 1,
            'lap_index' => $index + 1,
            'name' => sprintf('Lap %d', $index + 1),
            'elapsed_time' => property_exists($lap, 'TotalTimeSeconds') && null !== $lap->TotalTimeSeconds ? (int) round((float) $lap->TotalTimeSeconds) : 0,
            'moving_time' => property_exists($lap, 'TotalTimeSeconds') && null !== $lap->TotalTimeSeconds ? (int) round((float) $lap->TotalTimeSeconds) : 0,
            'distance' => property_exists($lap, 'DistanceMeters') && null !== $lap->DistanceMeters ? (float) $lap->DistanceMeters : 0.0,
            'average_speed' => property_exists($lap, 'TotalTimeSeconds') && null !== $lap->TotalTimeSeconds && (property_exists($lap, 'DistanceMeters') && null !== $lap->DistanceMeters) && (float) $lap->TotalTimeSeconds > 0
                ? (float) $lap->DistanceMeters / (float) $lap->TotalTimeSeconds
                : 0.0,
            'max_speed' => property_exists($lap, 'MaximumSpeed') && null !== $lap->MaximumSpeed ? (float) $lap->MaximumSpeed : 0.0,
            'total_elevation_gain' => 0.0,
            'average_heartrate' => property_exists($lap->AverageHeartRateBpm, 'Value') && null !== $lap->AverageHeartRateBpm->Value ? (int) $lap->AverageHeartRateBpm->Value : null,
            'start_date' => null !== $lapStart ? SerializableDateTime::fromTimestamp($lapStart)->format(\DateTimeInterface::ATOM) : null,
        ];
    }

    /**
     * @param array<string, list<mixed>> $streams
     */
    private function resolveStartingCoordinate(array $streams): ?Coordinate
    {
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

    /**
     * @return array<string, string>
     */
    private function extensionValues(\SimpleXMLElement $trackpoint): array
    {
        $values = [];
        if (!property_exists($trackpoint->Extensions, 'TPX') || null === $trackpoint->Extensions->TPX) {
            return $values;
        }

        foreach ($trackpoint->Extensions->TPX->children() as $name => $value) {
            $values[$name] = (string) $value;
        }

        return $values;
    }

    private function sumCalories(\SimpleXMLElement $activity): ?int
    {
        $calories = 0;
        $found = false;
        foreach ($activity->Lap as $lap) {
            if (property_exists($lap, 'Calories') && null !== $lap->Calories) {
                $calories += (int) $lap->Calories;
                $found = true;
            }
        }

        return $found ? $calories : null;
    }

    /**
     * @param list<array<string, mixed>> $laps
     */
    private function sumLapValues(array $laps, string $key): float
    {
        $sum = 0.0;
        foreach ($laps as $lap) {
            $sum += (float) ($lap[$key] ?? 0);
        }

        return $sum;
    }

    /**
     * @param list<mixed> $values
     */
    private function averageStream(array $values): ?int
    {
        $numbers = array_filter($values, static fn (mixed $v): bool => null !== $v);
        if ([] === $numbers) {
            return null;
        }

        return (int) round(array_sum($numbers) / count($numbers));
    }

    /**
     * @param list<mixed> $values
     */
    private function maxStream(array $values): ?int
    {
        $numbers = array_filter($values, static fn (mixed $v): bool => null !== $v);
        if ([] === $numbers) {
            return null;
        }

        return (int) max($numbers);
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

    private function mapSportType(string $sport, RawActivityFile $file): SportType
    {
        return match (strtolower($sport)) {
            'running' => SportType::RUN,
            'biking' => SportType::RIDE,
            'walking' => SportType::WALK,
            'hiking' => SportType::HIKE,
            'swimming' => SportType::SWIM,
            default => throw new CouldNotParseActivityFile(message: sprintf('Unsupported TCX sport "%s"', $sport), activityFile: $file),
        };
    }
}
