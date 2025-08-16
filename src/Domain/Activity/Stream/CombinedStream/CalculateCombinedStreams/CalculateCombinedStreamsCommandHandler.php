<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream\CombinedStream\CalculateCombinedStreams;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\ActivityStreams;
use App\Domain\Activity\Stream\CombinedStream\CombinedActivityStream;
use App\Domain\Activity\Stream\CombinedStream\CombinedActivityStreamRepository;
use App\Domain\Activity\Stream\CombinedStream\CombinedStreamType;
use App\Domain\Activity\Stream\CombinedStream\CombinedStreamTypes;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
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
            if (!$distanceStream = $streams->filterOnType(StreamType::DISTANCE)) {
                continue;
            }
            $streamTypes = CombinedStreamTypes::fromArray([
                CombinedStreamType::DISTANCE,
            ]);

            $otherStreams = ActivityStreams::empty();
            foreach (CombinedStreamTypes::othersFor($activity->getSportType()->getActivityType()) as $combinedStreamType) {
                if (!$stream = $streams->filterOnType($combinedStreamType->getStreamType())) {
                    continue;
                }
                if (!$stream->getData()) {
                    continue;
                }

                if (StreamType::ALTITUDE === $stream->getStreamType()) {
                    // Smoothen the altitude stream to remove noise and have a smooth line.
                    $stream = $stream->applySimpleMovingAverage(5);
                }

                $streamTypes->add($combinedStreamType);
                $otherStreams->add($stream);
            }

            $combinedData = new RamerDouglasPeucker(
                distanceStream: $distanceStream,
                movingStream: $streams->filterOnType(StreamType::MOVING),
                otherStreams: $otherStreams
            )->applyWith(Epsilon::create($activityType));

            $distanceIndex = array_search(CombinedStreamType::DISTANCE, $streamTypes->toArray(), true);
            $altitudeIndex = array_search(CombinedStreamType::ALTITUDE, $streamTypes->toArray(), true);
            $paceIndex = array_search(CombinedStreamType::PACE, $streamTypes->toArray(), true);

            // Make sure necessary streams are converted before saving,
            // So we do not need to convert it when reading the data.
            foreach ($combinedData as &$row) {
                $distanceInKm = Meter::from($row[$distanceIndex])->toKilometer();
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
                    $row[$altitudeIndex] = round($row[$altitudeIndex], 2);
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
