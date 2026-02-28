<?php

namespace App\Domain\Gear;

use App\Domain\Activity\Activities;
use App\Domain\Activity\Activity;
use App\Domain\Gear\ImportedGear\ImportedGear;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class GearStatistics
{
    private function __construct(
        private Gears $gears,
        private Activities $activities,
    ) {
    }

    public static function fromActivitiesAndGear(
        Activities $activities,
        Gears $gears): self
    {
        return new self(
            gears: $gears,
            activities: $activities
        );
    }

    public function getActiveGear(): Gears
    {
        $activeGear = $this->gears->filter(fn (Gear $gear): bool => !$gear->isRetired());

        $unspecifiedGear = $this->buildUnspecifiedGear();
        if ($unspecifiedGear instanceof Gear) {
            $activeGear->add($unspecifiedGear);
        }

        return $activeGear;
    }

    public function getRetiredGear(): Gears
    {
        return $this->gears->filter(fn (Gear $gear): bool => $gear->isRetired());
    }

    private function buildUnspecifiedGear(): ?Gear
    {
        $activitiesWithoutGear = $this->activities->filter(fn (Activity $activity): bool => !$activity->getGearId() instanceof GearId);
        $count = count($activitiesWithoutGear);

        if (0 === $count) {
            return null;
        }

        $distanceInMeter = Meter::from($activitiesWithoutGear->sum(fn (Activity $activity): float => $activity->getDistance()->toMeter()->toFloat()));
        $movingTimeInSeconds = (int) $activitiesWithoutGear->sum(fn (Activity $activity): int => $activity->getMovingTimeInSeconds());
        $elevation = Meter::from($activitiesWithoutGear->sum(fn (Activity $activity): float => $activity->getElevation()->toFloat()));
        $totalCalories = (int) $activitiesWithoutGear->sum(fn (Activity $activity): ?int => $activity->getCalories());

        return ImportedGear::create(
            gearId: GearId::none(),
            distanceInMeter: $distanceInMeter,
            createdOn: SerializableDateTime::fromString('1970-01-01'),
            name: 'Unspecified',
            isRetired: false,
        )
            ->withMovingTime(Seconds::from($movingTimeInSeconds))
            ->withElevation($elevation)
            ->withNumberOfActivities($count)
            ->withTotalCalories($totalCalories);
    }
}
