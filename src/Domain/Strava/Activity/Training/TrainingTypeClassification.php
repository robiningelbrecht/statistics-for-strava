<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Training;

final readonly class TrainingTypeClassification
{
    private function __construct(
        private TimeInZones $timeInZones
    ) {
    }

    public static function fromTimeInZones(TimeInZones $timeInZones): self
    {
        return new self($timeInZones);
    }

    public function getType(): TrainingType
    {
        $low = $this->timeInZones->getPercentageInLowZones();
        $medium = $this->timeInZones->getPercentageInMediumZone();
        $high = $this->timeInZones->getPercentageInHighZones();

        if ($low >= 75 && $low <= 85 && $medium >= 5 && $medium <= 10 && $high >= 10 && $high <= 20) {
            return TrainingType::POLARIZED;
        }

        if ($low >= 60 && $low <= 75 && $medium >= 15 && $medium <= 25 && $high >= 10 && $high <= 20) {
            return TrainingType::PYRAMIDAL;
        }

        if ($low >= 50 && $low <= 65 && $medium >= 25 && $medium <= 35 && $high >= 10 && $high <= 20) {
            return TrainingType::THRESHOLD;
        }

        if ($low >= 45 && $low <= 60 && $medium >= 15 && $medium <= 25 && $high >= 25 && $high <= 40) {
            return TrainingType::HIIT;
        }

        if ($low >= 85 && $low <= 95 && $medium >= 3 && $medium <= 8 && $high >= 2 && $high <= 7) {
            return TrainingType::BASE;
        }

        return TrainingType::UNIQUE_OTHER;
    }
}
