<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetric;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricType;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Console\ProgressIndicator;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPer100Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CalculateStreamValueDistribution implements CalculateActivityMetricsStep
{
    public function __construct(
        private ActivityStreamRepository $activityStreamRepository,
        private ActivityStreamMetricRepository $activityStreamMetricRepository,
        private ActivityRepository $activityRepository,
        private UnitSystem $unitSystem,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('=> Calculated value distribution for 0 streams');

        $countCalculatedStreams = 0;
        $activityIdsToProcess = $this->activityStreamMetricRepository->findActivityIdsWithoutDistributionValues();
        foreach ($activityIdsToProcess as $activityId) {
            $activity = $this->activityRepository->find($activityId);
            $sportType = $activity->getSportType();

            foreach (StreamType::thatSupportDistributionValues() as $streamType) {
                $stream = null;
                try {
                    $stream = $this->activityStreamRepository->findOneByActivityAndStreamType(
                        activityId: $activityId,
                        streamType: $streamType,
                    );
                } catch (EntityNotFound) {
                    $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
                        activityId: $activityId,
                        streamType: $streamType,
                        metricType: ActivityStreamMetricType::VALUE_DISTRIBUTION,
                        data: [],
                    ));

                    continue;
                }

                $valueDistribution = [];
                if (StreamType::WATTS === $streamType) {
                    /** @var array<int, int<1, max>> $valueDistribution */
                    $valueDistribution = array_count_values(array_filter($stream->getData(), fn (mixed $item): bool => !is_null($item)));
                    ksort($valueDistribution);
                } elseif (StreamType::HEART_RATE === $streamType) {
                    /** @var array<int, int<1, max>> $valueDistribution */
                    $valueDistribution = array_count_values($stream->getData());
                    ksort($valueDistribution);
                } elseif (StreamType::VELOCITY === $streamType) {
                    $velocityUnitPreference = $sportType->getVelocityDisplayPreference();
                    $filteredValues = array_filter($stream->getData(), fn (mixed $item): bool => !is_null($item));

                    $convertedValues = match (true) {
                        $velocityUnitPreference instanceof SecPer100Meter => array_map(
                            fn (float $item): int => (int) round(MetersPerSecond::from($item)->toSecPerKm()->toSecPer100Meter()->toFloat()),
                            $filteredValues
                        ),
                        $velocityUnitPreference instanceof SecPerKm => array_map(
                            fn (float $item): int => (int) round(MetersPerSecond::from($item)->toSecPerKm()->toUnitSystem($this->unitSystem)->toFloat()),
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
                }

                if ([] !== $valueDistribution) {
                    ++$countCalculatedStreams;
                }

                $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
                    activityId: $activityId,
                    streamType: $streamType,
                    metricType: ActivityStreamMetricType::VALUE_DISTRIBUTION,
                    data: $valueDistribution,
                ));
                $progressIndicator->updateMessage(sprintf('=> Calculated value distribution for %d streams', $countCalculatedStreams));
            }
        }

        $progressIndicator->finish(sprintf('=> Calculated value distribution for %d streams', $countCalculatedStreams));
    }
}
