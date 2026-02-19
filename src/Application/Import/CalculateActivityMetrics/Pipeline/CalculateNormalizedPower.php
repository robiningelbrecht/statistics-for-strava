<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetric;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricType;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Console\ProgressIndicator;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CalculateNormalizedPower implements CalculateActivityMetricsStep
{
    public function __construct(
        private ActivityStreamRepository $activityStreamRepository,
        private ActivityStreamMetricRepository $activityStreamMetricRepository,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('=> Calculated normalized power for 0 streams');

        $countCalculatedStreams = 0;
        $activityIdsToProcess = $this->activityStreamMetricRepository->findActivityIdsWithoutNormalizedPower();

        foreach ($activityIdsToProcess as $activityId) {
            $stream = $this->activityStreamRepository->findOneByActivityAndStreamType(
                activityId: $activityId,
                streamType: StreamType::WATTS
            );

            $powerData = $stream->getData();
            if (count($powerData) < 30) {
                $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
                    activityId: $activityId,
                    streamType: StreamType::WATTS,
                    metricType: ActivityStreamMetricType::NORMALIZED_POWER,
                    data: [0],
                ));

                continue;
            }

            $windowSize = 30;
            $movingAvg = [];
            $counter = count($powerData);

            for ($i = $windowSize - 1; $i < $counter; ++$i) {
                $window = array_slice($powerData, $i - $windowSize + 1, $windowSize);
                $avg = array_sum($window) / $windowSize;
                $movingAvg[] = $avg ** 4;
            }
            $avgPower = (array_sum($movingAvg) / count($movingAvg)) ** 0.25;

            ++$countCalculatedStreams;
            $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
                activityId: $activityId,
                streamType: StreamType::WATTS,
                metricType: ActivityStreamMetricType::NORMALIZED_POWER,
                data: [(int) round($avgPower)],
            ));

            $progressIndicator->updateMessage(sprintf('=> Calculated normalized power for %d streams', $countCalculatedStreams));
        }

        $progressIndicator->finish(sprintf('=> Calculated normalized power for %d streams', $countCalculatedStreams));
    }
}
