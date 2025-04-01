<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum WorkoutType: string implements TranslatableInterface
{
    case RACE = 'race';
    case WORKOUT = 'workout';
    case LONG_RUN = 'longRun';

    public static function fromStravaInt(?int $workoutType): ?self
    {
        if (is_null($workoutType)) {
            return null;
        }

        if (in_array($workoutType, [0, 10])) {
            // Equals to "none" in Strava.
            return null;
        }

        return match ($workoutType) {
            1, 11 => self::RACE,
            3, 12 => self::WORKOUT,
            2 => self::LONG_RUN,
            default => throw new \RuntimeException(sprintf('Workout type "%s" not supported', $workoutType)),
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::RACE => $translator->trans('Race', locale: $locale),
            self::WORKOUT => $translator->trans('Workout', locale: $locale),
            self::LONG_RUN => $translator->trans('Long run', locale: $locale),
        };
    }
}
