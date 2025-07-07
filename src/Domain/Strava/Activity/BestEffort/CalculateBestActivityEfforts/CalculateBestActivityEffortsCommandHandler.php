<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BestEffort\CalculateBestActivityEfforts;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\BestEffort\ActivityBestEffort;
use App\Domain\Strava\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class CalculateBestActivityEffortsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityBestEffortRepository $activityBestEffortRepository,
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof CalculateBestActivityEfforts);
        $command->getOutput()->writeln('Calculating best activity efforts...');

        $activityIdsWithoutBestEfforts = $this->activityBestEffortRepository->findActivityIdsThatNeedBestEffortsCalculation();

        $activityWithBestEffortsCalculatedCount = 0;
        foreach ($activityIdsWithoutBestEfforts as $activityId) {
            $distanceStream = $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::DISTANCE);
            $timeStream = $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::TIME);

            $activity = $this->activityRepository->find($activityId);
            $distances = $distanceStream->getData();
            $time = $timeStream->getData();

            if (!$activity->getSportType()->supportsBestEffortsStats()) {
                continue;
            }
            $distancesForBestEfforts = $activity->getSportType()->getActivityType()->getDistancesForBestEffortCalculation();
            if ((end($distances) - $distances[0]) < $distancesForBestEfforts[0]->toMeter()->toInt()) {
                // Activity is too short for best effort calculation.
                continue;
            }
            ++$activityWithBestEffortsCalculatedCount;

            foreach ($distancesForBestEfforts as $distance) {
                if ($activity->getDistance()->toMeter()->toInt() < $distance->toMeter()->toInt()) {
                    // For some reason the Strava distance indicates a longer distance than the actual activity distance.
                    // No clue why this happens, but it does.
                    // Make sure we don't calculate best efforts for distance streams that are longer than the activity distance.
                    continue;
                }
                $n = count($distances);
                $fastestTime = PHP_INT_MAX;
                $startIdx = 0;

                for ($endIdx = 0; $endIdx < $n; ++$endIdx) {
                    while ($startIdx < $endIdx && ($distances[$endIdx] - $distances[$startIdx]) >= $distance->toMeter()->toInt()) {
                        $fastestTime = min($fastestTime, $time[$endIdx] - $time[$startIdx]);
                        ++$startIdx;
                    }
                }

                if (PHP_INT_MAX === $fastestTime) {
                    // No fastest time for this distance.
                    continue;
                }

                $this->activityBestEffortRepository->add(
                    ActivityBestEffort::create(
                        activityId: $activityId,
                        distanceInMeter: $distance->toMeter(),
                        sportType: $activity->getSportType(),
                        timeInSeconds: $fastestTime,
                    )
                );
            }
        }
        $command->getOutput()->writeln(sprintf('  => Calculated best efforts for %d activities', $activityWithBestEffortsCalculatedCount));
    }
}
