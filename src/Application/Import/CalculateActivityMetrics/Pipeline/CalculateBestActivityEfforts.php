<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\BestEffort\ActivityBestEffort;
use App\Domain\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CalculateBestActivityEfforts implements CalculateActivityMetricsStep
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityBestEffortRepository $activityBestEffortRepository,
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $activityIdsWithoutBestEfforts = $this->activityBestEffortRepository->findActivityIdsThatNeedBestEffortsCalculation();

        $activityWithBestEffortsCalculatedCount = 0;
        foreach ($activityIdsWithoutBestEfforts as $activityId) {
            $distanceStream = $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::DISTANCE);
            $timeStream = $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::TIME);

            $activity = $this->activityRepository->find($activityId);
            $distances = $distanceStream->getData();
            $time = $timeStream->getData();

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

            if (0 !== $activityWithBestEffortsCalculatedCount % 10) {
                continue;
            }

            $output->writeln(sprintf(
                '  => %d/%d best efforts calculated',
                $activityWithBestEffortsCalculatedCount,
                count($activityIdsWithoutBestEfforts)
            ));
        }

        if (0 !== $activityWithBestEffortsCalculatedCount % 10) {
            $output->writeln(sprintf(
                '  => %d/%d best efforts calculated',
                $activityWithBestEffortsCalculatedCount,
                count($activityIdsWithoutBestEfforts)
            ));
        }
    }
}
