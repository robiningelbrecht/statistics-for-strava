<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream\CalculateCombinedStreams;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreams;
use App\Domain\Strava\Activity\Stream\CombinedStream\CombinedActivityStream;
use App\Domain\Strava\Activity\Stream\CombinedStream\CombinedActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
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

        $activityIdsThatNeedCombining = $this->combinedActivityStreamRepository->findActivityIdsThatNeedStreamCombining();
        foreach ($activityIdsThatNeedCombining as $activityId) {
            $activity = $this->activityRepository->find($activityId);

            if (!$activity->getSportType()->getActivityType()->supportsCombinedStreamCalculation()) {
                continue;
            }

            try {
                $distanceStream = $this->activityStreamRepository->findOneByActivityAndStreamType(
                    activityId: $activityId,
                    streamType: StreamType::DISTANCE
                );
                $altitudeStream = $this->activityStreamRepository->findOneByActivityAndStreamType(
                    activityId: $activityId,
                    streamType: StreamType::ALTITUDE
                );
            } catch (EntityNotFound) {
                continue;
            }

            $otherStreams = ActivityStreams::empty();

            $combinedData = new RamerDouglasPeucker(
                activityType: $activity->getSportType()->getActivityType(),
                distanceStream: $distanceStream,
                altitudeStream: $altitudeStream,
                otherStreams: $otherStreams
            )->apply();

            foreach (UnitSystem::cases() as $unitSystem) {
                $this->combinedActivityStreamRepository->add(
                    CombinedActivityStream::create(
                        activityId: $activityId,
                        unitSystem: $unitSystem,
                        data: $combinedData,
                    )
                );
            }
        }
    }
}
