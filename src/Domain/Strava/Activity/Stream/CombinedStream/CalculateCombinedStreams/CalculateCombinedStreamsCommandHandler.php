<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream\CalculateCombinedStreams;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreams;
use App\Domain\Strava\Activity\Stream\CombinedStream\CombinedActivityStream;
use App\Domain\Strava\Activity\Stream\CombinedStream\CombinedActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\CombinedStream\CombinedStreamType;
use App\Domain\Strava\Activity\Stream\CombinedStream\CombinedStreamTypes;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;

final readonly class CalculateCombinedStreamsCommandHandler implements CommandHandler
{
    use ProvideTimeFormats;

    public function __construct(
        private ActivityRepository $activityRepository,
        private CombinedActivityStreamRepository $combinedActivityStreamRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private UnitSystem $unitSystem,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof CalculateCombinedStreams);
        $command->getOutput()->writeln('Calculating combined activity streams...');

        $activityIdsThatNeedCombining = $this->combinedActivityStreamRepository->findActivityIdsThatNeedStreamCombining(
            $this->unitSystem
        );
        $activityWithCombinedStreamCalculatedCount = 0;
        foreach ($activityIdsThatNeedCombining as $activityId) {
            $activity = $this->activityRepository->find($activityId);
            $activityType = $activity->getSportType()->getActivityType();

            if (!$activityType->supportsCombinedStreamCalculation()) {
                continue;
            }

            $streams = $this->activityStreamRepository->findByActivityId($activityId);
            $streamTypes = CombinedStreamTypes::fromArray([
                CombinedStreamType::DISTANCE,
            ]);

            if (!$distanceStream = $streams->filterOnType(StreamType::DISTANCE)) {
                continue;
            }

            $otherStreams = ActivityStreams::empty();
            $elevationVariance = Meter::zero();
            $speedVariance = MetersPerSecond::zero();
            foreach (CombinedStreamType::othersFor($activity->getSportType()->getActivityType()) as $combinedStreamType) {
                if (!$stream = $streams->filterOnType($combinedStreamType->getStreamType())) {
                    continue;
                }
                if (!$stream->getData()) {
                    continue;
                }

                if (StreamType::ALTITUDE === $stream->getStreamType()) {
                    $data = $stream->getData();
                    $elevationVariance = Meter::from(max($data) - min($data));
                }
                if (StreamType::VELOCITY === $stream->getStreamType()) {
                    $data = $stream->getData();
                    $speedVariance = MetersPerSecond::from(max($data) - min($data));
                }
                $streamTypes->add($combinedStreamType);
                $otherStreams->add($stream);
            }

            $combinedData = new RamerDouglasPeucker(
                distanceStream: $distanceStream,
                movingStream: $streams->filterOnType(StreamType::MOVING),
                otherStreams: $otherStreams
            )->apply(Epsilon::create(
                totalDistance: $activity->getDistance()->toMeter(),
                elevationVariance: $elevationVariance,
                averageSpeed: $activity->getAverageSpeed()->toMetersPerSecond(),
                speedVariance: $speedVariance,
                activityType: $activityType
            ));

            // Make sure necessary streams are converted before saving,
            // so we do not need to convert it when reading the data.
            $distanceIndex = array_search(CombinedStreamType::DISTANCE, $streamTypes->toArray(), true);
            $altitudeIndex = array_search(CombinedStreamType::ALTITUDE, $streamTypes->toArray(), true);
            $paceIndex = array_search(CombinedStreamType::PACE, $streamTypes->toArray(), true);

            foreach ($combinedData as &$row) {
                $distanceInKm = Kilometer::from($row[$distanceIndex] / 1000);
                $row[$distanceIndex] = $distanceInKm->toFloat();

                if (false !== $paceIndex) {
                    $secondsPerKilometer = MetersPerSecond::from($row[$paceIndex])->toSecPerKm();
                    if (UnitSystem::IMPERIAL === $this->unitSystem) {
                        $row[$paceIndex] = $secondsPerKilometer->toSecPerMile()->toInt();
                    }
                    if (UnitSystem::METRIC === $this->unitSystem) {
                        $row[$paceIndex] = $secondsPerKilometer->toInt();
                    }
                }

                if (UnitSystem::IMPERIAL === $this->unitSystem) {
                    $row[$distanceIndex] = $distanceInKm->toMiles()->toFloat();
                    if (false !== $altitudeIndex) {
                        $row[$altitudeIndex] = Meter::from($row[$altitudeIndex])->toFoot()->toFloat();
                    }
                }

                // Apply rounding rules.
                $row[$distanceIndex] = match ($activityType) {
                    ActivityType::RIDE => $row[$distanceIndex] < 1 ? round($row[$distanceIndex], 1) : round($row[$distanceIndex]),
                    default => round($row[$distanceIndex], 1),
                };
                if (false !== $altitudeIndex) {
                    $row[$altitudeIndex] = round($row[$altitudeIndex]);
                }
            }

            $this->combinedActivityStreamRepository->add(
                CombinedActivityStream::create(
                    activityId: $activityId,
                    unitSystem: $this->unitSystem,
                    streamTypes: $streamTypes,
                    data: $combinedData,
                )
            );
            ++$activityWithCombinedStreamCalculatedCount;
        }
        $command->getOutput()->writeln(sprintf('  => Calculated combined streams for %d activities', $activityWithCombinedStreamCalculatedCount));
    }
}
