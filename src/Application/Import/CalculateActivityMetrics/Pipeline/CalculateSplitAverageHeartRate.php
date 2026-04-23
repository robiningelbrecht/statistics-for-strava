<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\Split\ActivitySplitRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Console\ProgressIndicator;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CalculateSplitAverageHeartRate implements CalculateActivityMetricsStep
{
    public function __construct(
        private ActivitySplitRepository $activitySplitRepository,
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('=> Calculated split avg heart rate for 0 activities');

        $countActivitiesProcessed = 0;
        $activityIdsToProcess = $this->activitySplitRepository->findActivityIdsWithoutAverageHeartRate();

        foreach ($activityIdsToProcess as $activityId) {
            try {
                $heartRateStream = $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::HEART_RATE);
            } catch (EntityNotFound) {
                continue;
            }

            $heartRateData = $heartRateStream->getData();
            if ([] === $heartRateData) {
                continue;
            }

            foreach (UnitSystem::cases() as $unitSystem) {
                $splits = $this->activitySplitRepository->findBy($activityId, $unitSystem);
                if ($splits->isEmpty()) {
                    continue;
                }

                $sumSplitMovingTimeInSeconds = 0;
                foreach ($splits as $split) {
                    $movingTimeInSeconds = $split->getMovingTimeInSeconds();
                    $heartRatesForCurrentSplit = array_slice(
                        array: $heartRateData,
                        offset: $sumSplitMovingTimeInSeconds,
                        length: $movingTimeInSeconds
                    );

                    if (0 === count($heartRatesForCurrentSplit)) {
                        continue;
                    }

                    $averageHeartRate = (int) round(array_sum($heartRatesForCurrentSplit) / count($heartRatesForCurrentSplit));
                    $this->activitySplitRepository->update($split->withAverageHeartRate($averageHeartRate));
                    $sumSplitMovingTimeInSeconds += $movingTimeInSeconds;
                }
            }

            ++$countActivitiesProcessed;
            $progressIndicator->updateMessage(sprintf('=> Calculated split avg heart rate for %d activities', $countActivitiesProcessed));
        }

        $progressIndicator->finish(sprintf('=> Calculated split avg heart rate for %d activities', $countActivitiesProcessed));
    }
}
