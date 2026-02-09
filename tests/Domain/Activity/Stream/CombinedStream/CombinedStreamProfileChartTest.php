<?php

namespace App\Tests\Domain\Activity\Stream\CombinedStream;

use App\Domain\Activity\Stream\CombinedStream\CombinedStreamProfileChart;
use App\Domain\Activity\Stream\CombinedStream\CombinedStreamType;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Contracts\Translation\TranslatorInterface;

class CombinedStreamProfileChartTest extends ContainerTestCase
{
    #[TestWith(data: [1, '65px'])]
    #[TestWith(data: [4, '75px'])]
    #[TestWith(data: [5, '85px'])]
    public function testGridLeftPadding(int $maxYAxisDigits, string $expectedPadding): void
    {
        $chart = CombinedStreamProfileChart::create(
            xAxisData: [],
            xAxisPosition: null,
            xAxisLabelSuffix: null,
            yAxisData: [2],
            maximumNumberOfDigitsOnYAxis: $maxYAxisDigits,
            yAxisStreamType: CombinedStreamType::VELOCITY,
            unitSystem: UnitSystem::METRIC,
            translator: $this->getContainer()->get(TranslatorInterface::class)
        )->build();

        $this->assertEquals(
            $expectedPadding,
            $chart['grid']['left']
        );
    }

    public function testItShouldThrowWhenYAxisDataIsEmpty(): void
    {
        $this->expectExceptionObject(new \RuntimeException('yAxisData data cannot be empty'));

        CombinedStreamProfileChart::create(
            xAxisData: [],
            xAxisPosition: null,
            xAxisLabelSuffix: null,
            yAxisData: [],
            maximumNumberOfDigitsOnYAxis: 100,
            yAxisStreamType: CombinedStreamType::VELOCITY,
            unitSystem: UnitSystem::METRIC,
            translator: $this->getContainer()->get(TranslatorInterface::class)
        )->build();
    }
}
