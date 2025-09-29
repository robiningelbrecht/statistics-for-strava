<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream\CombinedStream;

use App\Domain\Activity\Stream\StreamType;
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
    case VELOCITY = 'velocity';
    case PACE = 'pace';
    case LAT_LNG = 'latlng';

    public function getStreamType(): StreamType
    {
        return match ($this) {
            CombinedStreamType::PACE, CombinedStreamType::VELOCITY => StreamType::VELOCITY,
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
            CombinedStreamType::VELOCITY => $translator->trans('Speed'),
            default => throw new \RuntimeException(sprintf('Cannot translate CombinedStreamType "%s"', $this->value)),
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
            CombinedStreamType::VELOCITY => $unitSystem->speedSymbol(),
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
            CombinedStreamType::PACE,
            CombinedStreamType::VELOCITY => '#fac858',
            default => '#cccccc',
        };
    }
}
