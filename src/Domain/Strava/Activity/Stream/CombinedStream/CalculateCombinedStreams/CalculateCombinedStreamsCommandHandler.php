<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream\CalculateCombinedStreams;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreams;
use App\Domain\Strava\Activity\Stream\CombinedStream\CombinedActivityStream;
use App\Domain\Strava\Activity\Stream\CombinedStream\CombinedActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypes;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class CalculateCombinedStreamsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private CombinedActivityStreamRepository $combinedActivityStreamRepository,
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof CalculateCombinedStreams);
        $command->getOutput()->writeln('Calculating combined activity streams...');

        $activityIdsThatNeedCombining = $this->combinedActivityStreamRepository->findActivityIdsThatNeedStreamCombining();
        $activityWithCombinedStreamCalculatedCount = 0;
        foreach ($activityIdsThatNeedCombining as $activityId) {
            $activity = $this->activityRepository->find($activityId);
            $activityType = $activity->getSportType()->getActivityType();

            if (!$activityType->supportsCombinedStreamCalculation()) {
                continue;
            }

            $streams = $this->activityStreamRepository->findByActivityId($activityId);
            $streamTypes = StreamTypes::fromArray([
                StreamType::DISTANCE,
                StreamType::ALTITUDE,
            ]);

            if (!$distanceStream = $streams->filterOnType(StreamType::DISTANCE)) {
                continue;
            }
            if (!$altitudeStream = $streams->filterOnType(StreamType::ALTITUDE)) {
                continue;
            }

            $otherStreams = ActivityStreams::empty();
            foreach ([
                StreamType::WATTS,
                StreamType::HEART_RATE,
                StreamType::CADENCE] as $streamType) {
                if (!$stream = $streams->filterOnType($streamType)) {
                    continue;
                }
                if (!$stream->getData()) {
                    continue;
                }
                $streamTypes->add($streamType);
                $otherStreams->add($stream);
            }

            $combinedData = new RamerDouglasPeucker(
                activityType: $activityType,
                distanceStream: $distanceStream,
                altitudeStream: $altitudeStream,
                otherStreams: $otherStreams
            )->apply();

            foreach (UnitSystem::cases() as $unitSystem) {
                $this->combinedActivityStreamRepository->add(
                    CombinedActivityStream::create(
                        activityId: $activityId,
                        unitSystem: $unitSystem,
                        streamTypes: $streamTypes,
                        data: $combinedData,
                    )
                );
            }
            ++$activityWithCombinedStreamCalculatedCount;
        }
        $command->getOutput()->writeln(sprintf('  => Calculated combined streams for %d activities', $activityWithCombinedStreamCalculatedCount));
    }
}
