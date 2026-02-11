<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Infrastructure\Console\ProgressIndicator;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CalculateNormalizedPower implements CalculateActivityMetricsStep
{
    public function __construct(
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('=> Calculated normalized power for 0 streams');

        $countCalculatedStreams = 0;
        do {
            $streams = $this->activityStreamRepository->findWithoutNormalizedPower(100);
            /** @var \App\Domain\Activity\Stream\ActivityStream $stream */
            foreach ($streams as $stream) {
                $powerData = $stream->getData();
                if (count($powerData) < 30) {
                    $this->activityStreamRepository->update($stream->withNormalizedPower(0));

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
                $this->activityStreamRepository->update($stream->withNormalizedPower((int) round($avgPower)));
                $progressIndicator->updateMessage(sprintf('=> Calculated normalized power for %d streams', $countCalculatedStreams));
            }
        } while (!$streams->isEmpty());

        $progressIndicator->finish(sprintf('=> Calculated normalized power for %d streams', $countCalculatedStreams));
    }
}
