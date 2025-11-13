<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream\CalculateStreamValueDistribution;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPer100Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;

final readonly class CalculateStreamValueDistributionCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityStreamRepository $activityStreamRepository,
        private ActivityRepository $activityRepository,
        private UnitSystem $unitSystem,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof CalculateStreamValueDistribution);
        $command->getOutput()->writeln('Calculating stream value distributions...');

        $countCalculatedStreams = 0;
        do {
            $streams = $this->activityStreamRepository->findWithoutDistributionValues(100);

            /** @var \App\Domain\Activity\Stream\ActivityStream $stream */
            foreach ($streams as $stream) {
                ++$countCalculatedStreams;

                $activity = $this->activityRepository->find($stream->getActivityId());
                $sportType = $activity->getSportType();

                if (StreamType::WATTS === $stream->getStreamType()) {
                    $valueDistribution = array_count_values(array_filter($stream->getData(), fn (mixed $item): bool => !is_null($item)));
                    ksort($valueDistribution);
                    $stream->updateValueDistribution($valueDistribution);
                }
                if (StreamType::HEART_RATE === $stream->getStreamType()) {
                    $valueDistribution = array_count_values($stream->getData());
                    ksort($valueDistribution);

                    $stream->updateValueDistribution($valueDistribution);
                }
                if (StreamType::VELOCITY === $stream->getStreamType()) {
                    $velocityUnitPreference = $sportType->getVelocityDisplayPreference();
                    $filteredValues = array_filter($stream->getData(), fn (mixed $item): bool => !is_null($item));

                    $convertedValues = match (true) {
                        $velocityUnitPreference instanceof SecPer100Meter => array_map(
                            fn (float $item): int => (int) round(MetersPerSecond::from($item)->toSecPerKm()->toSecPer100Meter()->toFloat()),
                            $filteredValues
                        ),
                        $velocityUnitPreference instanceof SecPerKm && UnitSystem::METRIC === $this->unitSystem => array_map(
                            fn (float $item): int => (int) round(MetersPerSecond::from($item)->toSecPerKm()->toFloat()),
                            $filteredValues
                        ),
                        $velocityUnitPreference instanceof SecPerKm && UnitSystem::IMPERIAL === $this->unitSystem => array_map(
                            fn (float $item): int => (int) round(MetersPerSecond::from($item)->toSecPerKm()->toSecPerMile()->toFloat()),
                            $filteredValues
                        ),
                        UnitSystem::IMPERIAL === $this->unitSystem => array_map(
                            fn (float $item): int => (int) round(MetersPerSecond::from($item)->toKmPerHour()->toMph()->toFloat()),
                            $filteredValues
                        ),
                        default => array_map(
                            fn (float $item): int => (int) round(MetersPerSecond::from($item)->toKmPerHour()->toFloat()),
                            $filteredValues
                        ),
                    };

                    $valueDistribution = array_count_values($convertedValues);
                    ksort($valueDistribution, SORT_NUMERIC);
                    $stream->updateValueDistribution($valueDistribution);
                }

                $this->activityStreamRepository->update($stream);
            }
        } while (!$streams->isEmpty());

        $command->getOutput()->writeln(sprintf('  => Calculated value distribution for %d streams', $countCalculatedStreams));
    }
}
