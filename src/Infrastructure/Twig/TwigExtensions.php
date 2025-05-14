<?php

namespace App\Infrastructure\Twig;

use App\Domain\Strava\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
use App\Infrastructure\Time\Format\DateAndTimeFormat;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class TwigExtensions extends AbstractExtension
{
    public function __construct(
        private readonly Environment $twig,
        private readonly UnitSystem $unitSystem,
        private readonly DateAndTimeFormat $dateAndTimeFormat,
        private readonly MaintenanceTaskProgressCalculator $maintenanceTaskProgressCalculator,
    ) {
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('repeat', StringTwigExtension::doRepeat(...)),
            new TwigFilter('ellipses', StringTwigExtension::doEllipses(...)),
            new TwigFilter('countUppercaseChars', StringTwigExtension::doCountUpperCaseChars(...)),
            new TwigFilter('formatNumber', FormatNumberTwigExtension::doFormat(...)),
            new TwigFilter('formatDate', [new FormatDateAndTimeTwigExtension($this->dateAndTimeFormat), 'formatDate']),
            new TwigFilter('formatTime', [new FormatDateAndTimeTwigExtension($this->dateAndTimeFormat), 'formatTime']),
            new TwigFilter('convertMeasurement', [new MeasurementTwigExtension($this->unitSystem), 'doConversion']),
            new TwigFilter('formatPace', [new MeasurementTwigExtension($this->unitSystem), 'formatPace']),
            new TwigFilter('array_values', ArrayTwigExtension::doArrayValues(...)),
        ];
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('absoluteUrl', [new UrlTwigExtension(), 'toAbsoluteUrl']),
            new TwigFunction('renderComponent', [new RenderTemplateTwigExtension($this->twig), 'renderComponent']),
            new TwigFunction('renderSvg', [new RenderTemplateTwigExtension($this->twig), 'renderSvg']),
            new TwigFunction('renderUnitSymbol', [new MeasurementTwigExtension($this->unitSystem), 'getUnitSymbol']),
            new TwigFunction('calculateMaintenanceTaskProgress', [
                new MaintenanceTaskTwigExtension($this->maintenanceTaskProgressCalculator),
                'calculateProgress',
            ]),
        ];
    }
}
