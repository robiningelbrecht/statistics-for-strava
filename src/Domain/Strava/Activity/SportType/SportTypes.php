<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\SportType;

use App\Infrastructure\ValueObject\Collection;

final class SportTypes extends Collection
{
    public function getItemClassName(): string
    {
        return SportType::class;
    }

    public static function thatSupportPeakPowerOutputs(): SportTypes
    {
        return self::fromArray([
            SportType::RIDE,
            SportType::MOUNTAIN_BIKE_RIDE,
            SportType::GRAVEL_RIDE,
            SportType::VIRTUAL_RIDE,
        ]);
    }
}
