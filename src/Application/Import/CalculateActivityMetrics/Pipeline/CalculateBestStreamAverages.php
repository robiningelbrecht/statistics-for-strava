<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\Stream\ActivityPowerRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Infrastructure\Console\ProgressIndicator;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CalculateBestStreamAverages implements CalculateActivityMetricsStep
{
    public function __construct(
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('=> Calculated averages for 0 streams');

        $countCalculatedStreams = 0;
        do {
            $streams = $this->activityStreamRepository->findWithoutBestAverages(100);

            /** @var \App\Domain\Activity\Stream\ActivityStream $stream */
            foreach ($streams as $stream) {
                $bestAverages = [];
                foreach (ActivityPowerRepository::TIME_INTERVALS_IN_SECONDS_ALL as $timeIntervalInSeconds) {
                    if (!$stream->getStreamType()->supportsBestAverageCalculation()) {
                        continue;
                    }
                    if (!$bestAverage = $stream->calculateBestAverageForTimeInterval($timeIntervalInSeconds)) {
                        continue;
                    }
                    $bestAverages[$timeIntervalInSeconds] = $bestAverage;
                }
                ++$countCalculatedStreams;
                $stream->updateBestAverages($bestAverages);
                $this->activityStreamRepository->update($stream);

                $progressIndicator->updateMessage(sprintf('=> Calculated best averages for %d streams', $countCalculatedStreams));
            }
        } while (!$streams->isEmpty());

        $progressIndicator->finish(sprintf('=> Calculated best averages for %d streams', $countCalculatedStreams));
    }
}
