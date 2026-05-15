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

    public function calculateFor(ActivityId $activityId): ?float
    {
        $aerobicDecouplingMetric = $this->activityStreamMetricRepository
            ->findByActivityIdAndMetricType($activityId, ActivityStreamMetricType::AEROBIC_DECOUPLING)
            ->filterOnStreamType(StreamType::VELOCITY);

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
