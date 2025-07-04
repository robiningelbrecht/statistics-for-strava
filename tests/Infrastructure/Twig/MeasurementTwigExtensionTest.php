<?php

namespace App\Tests\Infrastructure\Twig;

use App\Infrastructure\Twig\MeasurementTwigExtension;
use App\Infrastructure\ValueObject\Measurement\Length\Foot;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Unit;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MeasurementTwigExtensionTest extends TestCase
{
    #[DataProvider(methodName: 'provideConversions')]
    public function testDoConversion(Unit $expectedMeasurement, UnitSystem $unitSystem, Unit $measurementToConvert): void
    {
        $extension = new MeasurementTwigExtension($unitSystem);

        $this->assertEquals(
            $expectedMeasurement,
            $extension->convertMeasurement($measurementToConvert)
        );
    }

    public function testFormatPace(): void
    {
        $extension = new MeasurementTwigExtension(UnitSystem::METRIC);

        $this->assertEquals(
            '10:00',
            $extension->formatPace(SecPerKm::from(600))
        );
    }

    #[DataProvider(methodName: 'provideUnitSymbols')]
    public function testGetUnitSymbol(string $expectedUnitSymbol, UnitSystem $unitSystem, string $unitName): void
    {
        $extension = new MeasurementTwigExtension($unitSystem);
        $this->assertEquals(
            $expectedUnitSymbol,
            $extension->getUnitSymbol($unitName),
        );
    }

    public function testGetUnitSymbolItShouldThrow(): void
    {
        $this->expectExceptionObject(new \RuntimeException('Invalid unitName "invalid"'));

        $extension = new MeasurementTwigExtension(UnitSystem::METRIC);
        $extension->getUnitSymbol('invalid');
    }

    public function testFormatNumber(): void
    {
        $extension = new MeasurementTwigExtension(UnitSystem::METRIC);

        $this->assertEquals(
            '1 000.33',
            $extension->formatNumber(1000.334, 2)
        );

        $this->assertEquals(
            0,
            $extension->formatNumber(null, 0)
        );
    }

    public static function provideConversions(): array
    {
        return [
            [Meter::from(3.048), UnitSystem::METRIC, Foot::from(10)],
            [Meter::from(10), UnitSystem::METRIC, Meter::from(10)],
            [Foot::from(9.998964), UnitSystem::IMPERIAL, Meter::from(3.048)],
            [Foot::from(10), UnitSystem::IMPERIAL, Foot::from(10)],
        ];
    }

    public static function provideUnitSymbols(): array
    {
        return [
            ['km', UnitSystem::METRIC, 'distance'],
            ['mi', UnitSystem::IMPERIAL, 'distance'],
            ['m', UnitSystem::METRIC, 'elevation'],
            ['ft', UnitSystem::IMPERIAL, 'elevation'],
        ];
    }
}
