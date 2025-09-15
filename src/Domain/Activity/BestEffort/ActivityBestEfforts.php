<?php

declare(strict_types=1);

namespace App\Domain\Activity\BestEffort;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Collection;
use App\Infrastructure\ValueObject\Measurement\Length\ConvertableToMeter;

final class ActivityBestEfforts extends Collection
{
    public function getItemClassName(): string
    {
        return ActivityBestEffort::class;
    }

    public function getUniqueSportTypes(): SportTypes
    {
        $sportTypes = SportTypes::empty();
        $uniqueSportTypes = array_unique($this->map(fn (ActivityBestEffort $activityBestEffort) => $activityBestEffort->getSportType()->value));

        foreach ($uniqueSportTypes as $uniqueSportType) {
            $sportTypes->add(SportType::from($uniqueSportType));
        }

        return $sportTypes;
    }

    public function getBySportType(SportType $sportType): ActivityBestEfforts
    {
        return $this->filter(fn (ActivityBestEffort $activityBestEffort) => $activityBestEffort->getSportType() === $sportType);
    }

    public function getByActivity(ActivityId $activityId): ActivityBestEfforts
    {
        return $this->filter(fn (ActivityBestEffort $activityBestEffort) => $activityBestEffort->getActivityId() == $activityId);
    }

    public function getOneBySportTypeAndDistance(SportType $sportType, ConvertableToMeter $distance): ?ActivityBestEffort
    {
        $activityBestEfforts = $this->filter(
            fn (ActivityBestEffort $activityBestEffort) => $activityBestEffort->getSportType() === $sportType
                && $activityBestEffort->getDistanceInMeter()->toInt() === $distance->toMeter()->toInt()
        );

        return $activityBestEfforts->getFirst();
    }

    public function getBySportTypeAndDistance(SportType $sportType, ConvertableToMeter $distance): ActivityBestEfforts
    {
        return $this->filter(
            fn (ActivityBestEffort $activityBestEffort) => $activityBestEffort->getSportType() === $sportType
                && $activityBestEffort->getDistanceInMeter()->toInt() === $distance->toMeter()->toInt()
        );
    }
}
