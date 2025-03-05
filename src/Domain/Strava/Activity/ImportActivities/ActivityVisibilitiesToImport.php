<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\ImportActivities;

use App\Domain\Strava\Activity\ActivityVisibility;
use App\Infrastructure\ValueObject\Collection;

final class ActivityVisibilitiesToImport extends Collection
{
    public function getItemClassName(): string
    {
        return ActivityVisibility::class;
    }

    /**
     * @param string[] $visibilities
     */
    public static function from(array $visibilities): self
    {
        if (0 === count($visibilities)) {
            // Import all visibilities.
            return self::fromArray(ActivityVisibility::cases());
        }

        return self::fromArray(array_map(
            fn (string $visibility) => ActivityVisibility::from($visibility),
            $visibilities,
        ));
    }
}
