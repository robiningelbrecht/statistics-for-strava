<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateBestStreamAverages;

use App\Domain\Activity\Stream\ActivityPowerRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class CalculateBestStreamAveragesCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof CalculateBestStreamAverages);
        $command->getOutput()->writeln('Calculating best stream averages...');

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
            }
        } while (!$streams->isEmpty());

        $command->getOutput()->writeln(sprintf('  => Calculated averages for %d streams', $countCalculatedStreams));
    }
}
