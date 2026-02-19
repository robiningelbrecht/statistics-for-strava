<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetric;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricType;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Console\ProgressIndicator;
use App\Infrastructure\ValueObject\Geography\EncodedPolyline;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CalculateEncodedPolylines implements CalculateActivityMetricsStep
{
    public function __construct(
        private ActivityStreamRepository $activityStreamRepository,
        private ActivityStreamMetricRepository $activityStreamMetricRepository,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('=> Calculated encoded polyline for 0 streams');

        $countCalculatedStreams = 0;
        $activityIdsToProcess = $this->activityStreamMetricRepository->findActivityIdsWithoutEncodedPolyline();

        foreach ($activityIdsToProcess as $activityId) {
            $stream = $this->activityStreamRepository->findOneByActivityAndStreamType(
                activityId: $activityId,
                streamType: StreamType::LAT_LNG
            );

            $latLngData = $stream->getData();
            if ([] === $latLngData) {
                $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
                    activityId: $activityId,
                    streamType: StreamType::LAT_LNG,
                    metricType: ActivityStreamMetricType::ENCODED_POLYLINE,
                    data: [],
                ));

                continue;
            }

            $encodedPolyline = EncodedPolyline::fromCoordinates($latLngData);

            ++$countCalculatedStreams;
            $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
                activityId: $activityId,
                streamType: StreamType::LAT_LNG,
                metricType: ActivityStreamMetricType::ENCODED_POLYLINE,
                data: [(string) $encodedPolyline],
            ));

            $progressIndicator->updateMessage(sprintf('=> Calculated encoded polyline for %d streams', $countCalculatedStreams));
        }

        $progressIndicator->finish(sprintf('=> Calculated encoded polyline for %d streams', $countCalculatedStreams));
    }
}
