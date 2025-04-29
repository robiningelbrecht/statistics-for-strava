<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BestEffort;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'ActivityBestEffort_sportTypeIndex', columns: ['sportType'])]
final readonly class ActivityBestEffort
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private ActivityId $activityId,
        #[ORM\Id, ORM\Column(type: 'integer')]
        private Meter $distanceInMeter,
        #[ORM\Column(type: 'string')]
        private SportType $sportType,
        #[ORM\Column(type: 'integer')]
        private int $timeInSeconds,
    ) {
    }

    public static function create(
        ActivityId $activityId,
        Meter $distanceInMeter,
        SportType $sportType,
        int $timeInSeconds,
    ): self {
        return new self(
            activityId: $activityId,
            distanceInMeter: $distanceInMeter,
            sportType: $sportType,
            timeInSeconds: $timeInSeconds
        );
    }

    public static function fromState(
        ActivityId $activityId,
        Meter $distanceInMeter,
        SportType $sportType,
        int $timeInSeconds,
    ): self {
        return new self(
            activityId: $activityId,
            distanceInMeter: $distanceInMeter,
            sportType: $sportType,
            timeInSeconds: $timeInSeconds
        );
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getSportType(): SportType
    {
        return $this->sportType;
    }

    public function getDistanceInMeter(): Meter
    {
        return $this->distanceInMeter;
    }

    public function getTimeInSeconds(): int
    {
        return $this->timeInSeconds;
    }
}
