<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BestEffort;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Collection;

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
}
