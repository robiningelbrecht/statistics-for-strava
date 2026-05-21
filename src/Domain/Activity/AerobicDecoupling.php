<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Domain\Activity\Stream\Metric\ActivityStreamMetricRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricType;
use App\Domain\Activity\Stream\StreamType;

final readonly class AerobicDecoupling
{
    public function __construct(
        private ActivityStreamMetricRepository $activityStreamMetricRepository,
    ) {
    }

    public function calculateFor(Activity $activity): ?float
    {
        $streamType = match ($activity->getSportType()->getActivityType()) {
            ActivityType::RIDE => StreamType::WATTS,
            default => StreamType::VELOCITY,
        };
        $aerobicDecouplingMetrics = $this->activityStreamMetricRepository
            ->findByActivityIdAndMetricType($activity->getId(), ActivityStreamMetricType::AEROBIC_DECOUPLING);
        $aerobicDecouplingMetric = $aerobicDecouplingMetrics->filterOnStreamType($streamType)
            ?? $aerobicDecouplingMetrics->filterOnStreamType(StreamType::VELOCITY)
            ?? $aerobicDecouplingMetrics->filterOnStreamType(StreamType::WATTS);

        if (!$aerobicDecouplingMetric instanceof Stream\Metric\ActivityStreamMetric) {
            return null;
        }

        $data = $aerobicDecouplingMetric->getData();
        if (!isset($data[0]) || !is_numeric($data[0])) {
            return null;
        }

        return (float) $data[0];
    }

    public function determineSeverity(?float $aerobicDecoupling): ?string
    {
        return match (true) {
            null === $aerobicDecoupling => null,
            $aerobicDecoupling < 5.0 => 'stable',
            $aerobicDecoupling <= 10.0 => 'moderate',
            default => 'significant',
        };
    }
}
