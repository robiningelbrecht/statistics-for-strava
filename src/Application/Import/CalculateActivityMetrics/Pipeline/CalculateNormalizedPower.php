<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\Stream\ActivityStreamRepository;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CalculateNormalizedPower implements CalculateActivityMetricsStep
{
    public function __construct(
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $countCalculatedStreams = 0;
        do {
            $streams = $this->activityStreamRepository->findWithoutNormalizedPower(100);
            /** @var \App\Domain\Activity\Stream\ActivityStream $stream */
            foreach ($streams as $stream) {
                $powerData = $stream->getData();
                if (count($powerData) < 30) {
                    $stream->updateNormalizedPower(0);
                    $this->activityStreamRepository->update($stream);

                    continue;
                }

                $windowSize = 30;
                $movingAvg = [];

                for ($i = $windowSize - 1; $i < count($powerData); ++$i) {
                    $window = array_slice($powerData, $i - $windowSize + 1, $windowSize);
                    $avg = array_sum($window) / $windowSize;
                    $movingAvg[] = $avg ** 4;
                }
                $avgPower = (array_sum($movingAvg) / count($movingAvg)) ** 0.25;

                ++$countCalculatedStreams;
                $stream->updateNormalizedPower((int) round($avgPower));
                $this->activityStreamRepository->update($stream);
            }
        } while (!$streams->isEmpty());

        $output->writeln(sprintf('  => Calculated normalized power for %d streams', $countCalculatedStreams));
    }
}
