<?php

namespace App\Tests\Domain\Activity\Stream\CombinedStream;

use App\Domain\Activity\Stream\CombinedStream\CombinedStreamProfileCharts;
use App\Domain\Activity\Stream\CombinedStream\CombinedStreamType;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Contracts\Translation\TranslatorInterface;

class CombinedStreamProfileChartsTest extends ContainerTestCase
{
    public function testItUsesPaceFormattingForGapSeries(): void
    {
        $chart = CombinedStreamProfileCharts::create(
            items: [
                ['yAxisData' => [301.2, 299.8, 298.9], 'yAxisStreamType' => CombinedStreamType::GAP],
                ['yAxisData' => [300.4, 301.1, 302.0], 'yAxisStreamType' => CombinedStreamType::PACE],
            ],
            topXAxisData: [1, 2, 3],
            bottomXAxisData: [0.5, 1.0, 1.5],
            bottomXAxisSuffix: 'km',
            grades: [],
            maximumNumberOfDigitsOnYAxis: 3,
            unitSystem: UnitSystem::METRIC,
            translator: $this->getContainer()->get(TranslatorInterface::class)
        )->build();

        $this->assertSame('callback:formatSecondsTrimZero', $chart['yAxis'][0]['axisLabel']['formatter']);
        $this->assertSame('callback:formatSecondsTrimZero', $chart['yAxis'][1]['axisLabel']['formatter']);
        $this->assertSame('__gap', $chart['series'][0]['name']);
        $this->assertSame('__pace', $chart['series'][1]['name']);
    }

    #[TestWith(data: [1, '65px'])]
    #[TestWith(data: [4, '75px'])]
    #[TestWith(data: [5, '85px'])]
    public function testGridLeftPadding(int $maxYAxisDigits, string $expectedPadding): void
    {
        $chart = CombinedStreamProfileCharts::create(
            items: [
                ['yAxisData' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 'yAxisStreamType' => CombinedStreamType::WATTS],
            ],
            topXAxisData: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            bottomXAxisData: [],
            bottomXAxisSuffix: null,
            grades: [],
            maximumNumberOfDigitsOnYAxis: $maxYAxisDigits,
            unitSystem: UnitSystem::METRIC,
            translator: $this->getContainer()->get(TranslatorInterface::class)
        )->build();

        $this->assertEquals(
            $expectedPadding,
            $chart['grid'][0]['left']
        );
    }

    public function testItShouldThrowWhenYAxisDataIsEmpty(): void
    {
        $this->expectExceptionObject(new \RuntimeException('yAxisData data cannot be empty'));

        CombinedStreamProfileCharts::create(
            items: [
                ['yAxisData' => [], 'yAxisStreamType' => CombinedStreamType::WATTS],
            ],
            topXAxisData: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            bottomXAxisData: [],
            bottomXAxisSuffix: null,
            grades: [],
            maximumNumberOfDigitsOnYAxis: 3,
            unitSystem: UnitSystem::METRIC,
            translator: $this->getContainer()->get(TranslatorInterface::class)
        )->build();
    }
}
