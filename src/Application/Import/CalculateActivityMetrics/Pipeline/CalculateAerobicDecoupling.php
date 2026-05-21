<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetric;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricType;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Console\ProgressIndicator;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CalculateAerobicDecoupling implements CalculateActivityMetricsStep
{
    private int $minimumMovingTimeInSeconds;

    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private ActivityStreamMetricRepository $activityStreamMetricRepository,
        private AerobicDecouplingCalculator $aerobicDecouplingCalculator,
        int $minimumMovingTimeInMinutes,
    ) {
        if ($minimumMovingTimeInMinutes < 0) {
            throw new \InvalidArgumentException(sprintf('config/app/config.yaml metrics.aerobicDecoupling.minimumMovingTimeInMinutes must be 0 or greater, got %d', $minimumMovingTimeInMinutes));
        }

        $this->minimumMovingTimeInSeconds = $minimumMovingTimeInMinutes * 60;
    }

    public function process(OutputInterface $output): void
    {
        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('=> Calculated aerobic decoupling for 0 activities');

        $countActivitiesProcessed = 0;
        $activityIdsToProcess = $this->activityStreamMetricRepository->findActivityIdsWithoutAerobicDecoupling(
            $this->minimumMovingTimeInSeconds
        );

        foreach ($activityIdsToProcess as $activityId) {
            $activityType = $this->activityRepository->find($activityId)->getSportType()->getActivityType();
            $decoupling = match ($activityType) {
                ActivityType::RUN => $this->aerobicDecouplingCalculator->calculateForRun(
                    timeData: $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::TIME)->getData(),
                    movingData: $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::MOVING)->getData(),
                    heartRateData: $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::HEART_RATE)->getData(),
                    velocityData: $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::VELOCITY)->getData(),
                ),
                ActivityType::RIDE => $this->aerobicDecouplingCalculator->calculateForRide(
                    timeData: $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::TIME)->getData(),
                    movingData: $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::MOVING)->getData(),
                    heartRateData: $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::HEART_RATE)->getData(),
                    powerData: $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::WATTS)->getData(),
                ),
                default => null,
            };
            $streamType = match ($activityType) {
                ActivityType::RIDE => StreamType::WATTS,
                default => StreamType::VELOCITY,
            };

            $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
                activityId: $activityId,
                streamType: $streamType,
                metricType: ActivityStreamMetricType::AEROBIC_DECOUPLING,
                data: [null === $decoupling ? null : round($decoupling, 4)],
            ));

            ++$countActivitiesProcessed;
            $progressIndicator->updateMessage(sprintf('=> Calculated aerobic decoupling for %d activities', $countActivitiesProcessed));
        }

        $progressIndicator->finish(sprintf('=> Calculated aerobic decoupling for %d activities', $countActivitiesProcessed));
    }
}
