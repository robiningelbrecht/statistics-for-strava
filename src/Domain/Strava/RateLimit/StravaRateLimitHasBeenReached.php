<?php

declare(strict_types=1);

namespace App\Domain\Strava\RateLimit;

final class StravaRateLimitHasBeenReached extends \RuntimeException
{
    public static function dailyReadLimit(): self
    {
        return new self('You reached the daily Strava API rate limit. You will need to import the rest of your data tomorrow');
    }

    public static function fifteenMinuteReadLimit(int $minutesUntilNextFifteenMinuteInterval): self
    {
        return new self(sprintf('You reached the 15-minute Strava API rate limit. Try again in %s minutes', $minutesUntilNextFifteenMinuteInterval));
    }
}
