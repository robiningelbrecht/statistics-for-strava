<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\Stream\ActivityPowerRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetric;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricType;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Console\ProgressIndicator;
use App\Infrastructure\Exception\EntityNotFound;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CalculateBestStreamAverages implements CalculateActivityMetricsStep
{
    public function __construct(
        private ActivityStreamRepository $activityStreamRepository,
        private ActivityStreamMetricRepository $activityStreamMetricRepository,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('=> Calculated averages for 0 streams');

        $countCalculatedStreams = 0;
        $activityIdsToProcess = $this->activityStreamMetricRepository->findActivityIdsWithoutBestAverages();

        foreach ($activityIdsToProcess as $activityId) {
            foreach (StreamType::thatSupportBestAverageCalculation() as $streamType) {
                $bestAverages = [];
                $stream = null;
                try {
                    $stream = $this->activityStreamRepository->findOneByActivityAndStreamType(
                        activityId: $activityId,
                        streamType: $streamType,
                    );
                } catch (EntityNotFound) {
                }

                if (!$stream?->getData()) {
                    $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
                        activityId: $activityId,
                        streamType: $streamType,
                        metricType: ActivityStreamMetricType::BEST_AVERAGES,
                        data: [],
                    ));

                    continue;
                }

                foreach (ActivityPowerRepository::TIME_INTERVALS_IN_SECONDS_ALL as $timeIntervalInSeconds) {
                    if (!$bestAverage = $stream->calculateBestAverageForTimeInterval($timeIntervalInSeconds)) {
                        continue;
                    }
                    $bestAverages[$timeIntervalInSeconds] = $bestAverage;
                }

                ++$countCalculatedStreams;
                $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
                    activityId: $activityId,
                    streamType: $streamType,
                    metricType: ActivityStreamMetricType::BEST_AVERAGES,
                    data: $bestAverages,
                ));

                $progressIndicator->updateMessage(sprintf('=> Calculated best averages for %d streams', $countCalculatedStreams));
            }
        }

        $progressIndicator->finish(sprintf('=> Calculated best averages for %d streams', $countCalculatedStreams));
    }
}
