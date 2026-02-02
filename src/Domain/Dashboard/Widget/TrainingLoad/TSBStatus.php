<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingLoad;

use App\Infrastructure\Localisation\TranslatableWithDescription;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TSBStatus implements TranslatableWithDescription
{
    case POSSIBLE_DETRAINING;   // > 25
    case PEAK_FRESH;           // 10 – 25
    case SLIGHTLY_FRESH;       // 0 – 10
    case NEUTRAL;              // -10 – 0
    case ACCUMULATED_FATIGUE;  // -30 - -10
    case OVER_FATIGUED;        // < -30

    public static function fromFloat(float $tsb): self
    {
        return match (true) {
            $tsb > 25 => self::POSSIBLE_DETRAINING,
            $tsb > 10 => self::PEAK_FRESH,
            $tsb > 0 => self::SLIGHTLY_FRESH,
            $tsb > -10 => self::NEUTRAL,
            $tsb > -30 => self::ACCUMULATED_FATIGUE,
            default => self::OVER_FATIGUED,
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::POSSIBLE_DETRAINING => $translator->trans('Risk of detraining', locale: $locale),
            self::PEAK_FRESH => $translator->trans('Peak fresh', locale: $locale),
            self::SLIGHTLY_FRESH => $translator->trans('Slightly fresh', locale: $locale),
            self::NEUTRAL => $translator->trans('Neutral', locale: $locale),
            self::ACCUMULATED_FATIGUE => $translator->trans('Accumulated fatigue', locale: $locale),
            self::OVER_FATIGUED => $translator->trans('Over-fatigued', locale: $locale),
        };
    }

    public function transDescription(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::POSSIBLE_DETRAINING => $translator->trans('Fitness may be declining due to low recent training load', locale: $locale),
            self::PEAK_FRESH => $translator->trans('Highly recovered and ready to perform', locale: $locale),
            self::SLIGHTLY_FRESH => $translator->trans('Light fatigue with good readiness. A great balance for quality training or longer efforts', locale: $locale),
            self::NEUTRAL => $translator->trans('Moderate fatigue with stable fitness. Well suited for consistent day-to-day training', locale: $locale),
            self::ACCUMULATED_FATIGUE => $translator->trans('Training load is accumulating, recovery days are important', locale: $locale),
            self::OVER_FATIGUED => $translator->trans('High fatigue levels may increase injury or burnout risk. Consider reducing load and prioritizing recovery', locale: $locale),
        };
    }

    public function getRange(): string
    {
        return match ($this) {
            self::POSSIBLE_DETRAINING => '> 25',
            self::PEAK_FRESH => '10 to 25',
            self::SLIGHTLY_FRESH => '0 to 10',
            self::NEUTRAL => '-10 to 0',
            self::ACCUMULATED_FATIGUE => '-30 to -10',
            self::OVER_FATIGUED => '< -30',
        };
    }

    public function getTextColor(): string
    {
        return match ($this) {
            self::PEAK_FRESH, self::SLIGHTLY_FRESH => 'text-green-600',
            self::NEUTRAL => 'text-gray-900',
            self::POSSIBLE_DETRAINING => 'text-orange-500',
            self::ACCUMULATED_FATIGUE => 'text-yellow-600',
            self::OVER_FATIGUED => 'text-red-600',
        };
    }

    public function getPillColors(): string
    {
        return match ($this) {
            self::PEAK_FRESH, self::SLIGHTLY_FRESH => 'bg-green-100 text-green-800',
            self::NEUTRAL => 'bg-gray-200 text-gray-900',
            self::POSSIBLE_DETRAINING => 'bg-orange-100 text-orange-800',
            self::ACCUMULATED_FATIGUE => 'bg-yellow-100 text-yellow-800',
            self::OVER_FATIGUED => 'bg-red-100 text-red-800',
        };
    }
}
