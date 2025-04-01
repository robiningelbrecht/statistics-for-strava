<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum CombinedStreamType: string implements TranslatableInterface
{
    case DISTANCE = 'distance';
    case ALTITUDE = 'altitude';
    case WATTS = 'watts';
    case CADENCE = 'cadence';
    case HEART_RATE = 'heartrate';
    case PACE = 'pace';

    public function getStreamType(): StreamType
    {
        return match ($this) {
            CombinedStreamType::PACE => StreamType::VELOCITY,
            default => StreamType::from($this->value),
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            CombinedStreamType::DISTANCE => $translator->trans('Distance'),
            CombinedStreamType::ALTITUDE => $translator->trans('Elevation'),
            CombinedStreamType::HEART_RATE => $translator->trans('Heart rate'),
            CombinedStreamType::CADENCE => $translator->trans('Cadence'),
            CombinedStreamType::WATTS => $translator->trans('Power'),
            CombinedStreamType::PACE => $translator->trans('Pace'),
        };
    }

    public function getSuffix(UnitSystem $unitSystem): string
    {
        return match ($this) {
            CombinedStreamType::HEART_RATE => 'bpm',
            CombinedStreamType::CADENCE => 'rpm',
            CombinedStreamType::WATTS => 'watt',
            CombinedStreamType::PACE => $unitSystem->paceSymbol(),
            CombinedStreamType::ALTITUDE => $unitSystem->elevationSymbol(),
            default => throw new \RuntimeException('Suffix not supported for '.$this->value),
        };
    }

    public function getSeriesColor(): string
    {
        return match ($this) {
            CombinedStreamType::ALTITUDE => '#a6a6a6',
            CombinedStreamType::HEART_RATE => '#ee6666',
            CombinedStreamType::CADENCE => '#91cc75',
            CombinedStreamType::WATTS => '#73c0de',
            CombinedStreamType::PACE => '#fac858',
            default => '#cccccc',
        };
    }

    /**
     * @return array<CombinedStreamType>
     */
    public static function othersFor(ActivityType $activityType): array
    {
        if (ActivityType::RIDE === $activityType) {
            return [
                self::ALTITUDE,
                self::HEART_RATE,
                self::WATTS,
                self::CADENCE,
            ];
        }

        return [
            self::ALTITUDE,
            self::HEART_RATE,
            self::PACE,
        ];
    }
}
