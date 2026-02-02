<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingLoad;

use App\Infrastructure\Localisation\TranslatableWithDescription;
use Symfony\Contracts\Translation\TranslatorInterface;

enum AcRatioStatus implements TranslatableWithDescription
{
    case HIGH_RISK; // > 1.3
    case LOW_RISK; // 0.8 - 1.3
    case LOW_TRAINING_LOAD; // < 0.8

    public static function fromFloat(float $acRatio): self
    {
        return match (true) {
            $acRatio > 1.3 => self::HIGH_RISK,
            $acRatio < 0.8 => self::LOW_TRAINING_LOAD,
            default => self::LOW_RISK,
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::HIGH_RISK => $translator->trans('High risk', locale: $locale),
            self::LOW_RISK => $translator->trans('Low risk', locale: $locale),
            self::LOW_TRAINING_LOAD => $translator->trans('Low training load', locale: $locale),
        };
    }

    public function transDescription(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::HIGH_RISK => $translator->trans('Consider reducing load', locale: $locale),
            self::LOW_RISK => $translator->trans('Optimal training range', locale: $locale),
            self::LOW_TRAINING_LOAD => $translator->trans('Fitness may decline', locale: $locale),
        };
    }

    public function getRange(): string
    {
        return match ($this) {
            self::HIGH_RISK => '> 1.3',
            self::LOW_RISK => '0.8 to 1.3',
            self::LOW_TRAINING_LOAD => '< 0.8',
        };
    }

    public function getTextColor(): string
    {
        return match ($this) {
            self::LOW_RISK => 'text-green-600',
            self::LOW_TRAINING_LOAD => 'text-yellow-600',
            self::HIGH_RISK => 'text-red-600',
        };
    }

    public function getPillColors(): string
    {
        return match ($this) {
            self::LOW_RISK => 'bg-green-100 text-green-800',
            self::LOW_TRAINING_LOAD => 'bg-yellow-100 text-yellow-800',
            self::HIGH_RISK => 'bg-red-100 text-red-800',
        };
    }
}
