<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Imperial;
use App\Infrastructure\ValueObject\Measurement\Metric;
use App\Infrastructure\ValueObject\Measurement\Unit;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final readonly class MeasurementTwigExtension
{
    use ProvideTimeFormats;

    public function __construct(
        private UnitSystem $unitSystem,
    ) {
    }

    #[AsTwigFilter('convertMeasurement')]
    public function doConversion(Unit $measurement): Unit
    {
        if (UnitSystem::IMPERIAL === $this->unitSystem && $measurement instanceof Metric) {
            return $measurement->toImperial();
        }
        if (UnitSystem::METRIC === $this->unitSystem && $measurement instanceof Imperial) {
            return $measurement->toMetric();
        }

        return $measurement;
    }

    #[AsTwigFilter('formatPace')]
    public function formatPace(SecPerKm $pace): string
    {
        $pace = $pace->toUnitSystem($this->unitSystem);

        return $this->formatDurationForHumans($pace->toInt());
    }

    #[AsTwigFunction('renderUnitSymbol')]
    public function getUnitSymbol(string $unitName): string
    {
        return match ($unitName) {
            'distance' => $this->unitSystem->distanceSymbol(),
            'elevation' => $this->unitSystem->elevationSymbol(),
            'carbon-saved' => $this->unitSystem->carbonSavedSymbol(),
            'pace' => $this->unitSystem->paceSymbol(),
            default => throw new \RuntimeException(sprintf('Invalid unitName "%s"', $unitName)),
        };
    }
}
