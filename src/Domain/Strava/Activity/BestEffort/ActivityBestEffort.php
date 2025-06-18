<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BestEffort;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'ActivityBestEffort_sportTypeIndex', columns: ['sportType'])]
final class ActivityBestEffort
{
    use ProvideTimeFormats;

    private ?Activity $activity = null;

    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private readonly ActivityId $activityId,
        #[ORM\Id, ORM\Column(type: 'integer')]
        private readonly Meter $distanceInMeter,
        #[ORM\Column(type: 'string')]
        private readonly SportType $sportType,
        #[ORM\Column(type: 'integer')]
        private readonly int $timeInSeconds,
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

    public function getFormattedTimeInSeconds(): string
    {
        return $this->formatDurationForChartLabel($this->getTimeInSeconds());
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function enrichWithActivity(Activity $activity): void
    {
        $this->activity = $activity;
    }
}
