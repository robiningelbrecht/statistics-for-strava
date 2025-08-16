<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream\CalculateNormalizedPower;

use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class CalculateNormalizedPowerCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof CalculateNormalizedPower);
        $command->getOutput()->writeln('Calculating normalized power...');

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
                    $movingAvg[] = pow($avg, 4);
                }
                $avgPower = pow(array_sum($movingAvg) / count($movingAvg), 0.25);

                ++$countCalculatedStreams;
                $stream->updateNormalizedPower((int) round($avgPower));
                $this->activityStreamRepository->update($stream);
            }
        } while (!$streams->isEmpty());

        $command->getOutput()->writeln(sprintf('  => Calculated normalized power for %d streams', $countCalculatedStreams));
    }
}
