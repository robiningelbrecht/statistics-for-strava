<?php

namespace App\Tests\Infrastructure\Time\Format;

use App\Infrastructure\Time\Format\ProvideTimeFormats;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProvideTimeFormatsTest extends TestCase
{
    use ProvideTimeFormats;

    #[DataProvider(methodName: 'provideTimeFormatsForHumans')]
    public function testFormatDurationForHumans(int $timeInSeconds, string $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            $this->formatDurationForHumans($timeInSeconds)
        );
    }

    #[DataProvider(methodName: 'provideTimeFormatsForHumansForChartLabel')]
    public function testFormatDurationForChartLabel(int $timeInSeconds, string $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            $this->formatDurationForChartLabel($timeInSeconds)
        );
    }

    #[DataProvider(methodName: 'provideTimeFormatsForHumansWithoutTrimming')]
    public function testFormatDurationForHumansWithoutTrimming(int $timeInSeconds, string $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            $this->formatDurationForHumansWithoutTrimming($timeInSeconds)
        );
    }

    public static function provideTimeFormatsForHumans(): array
    {
        return [
            [10, '10s'],
            [61, '1:01'],
            [601, '10:01'],
            [7312, '2:01:52'],
        ];
    }

    public static function provideTimeFormatsForHumansForChartLabel(): array
    {
        return [
            [10, '00:10'],
            [61, '01:01'],
            [601, '10:01'],
            [7312, '2:01:52'],
        ];
    }

    public static function provideTimeFormatsForHumansWithoutTrimming(): array
    {
        return [
            [10, '00:00:10'],
            [61, '00:01:01'],
            [601, '00:10:01'],
            [7312, '02:01:52'],
        ];
    }
}
