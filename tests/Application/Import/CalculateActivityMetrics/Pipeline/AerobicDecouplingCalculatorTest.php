<?php

declare(strict_types=1);

namespace App\Tests\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Application\Import\CalculateActivityMetrics\Pipeline\AerobicDecouplingCalculator;
use PHPUnit\Framework\TestCase;

final class AerobicDecouplingCalculatorTest extends TestCase
{
    private AerobicDecouplingCalculator $calculator;

    public function testCalculateReturnsZeroForStableRun(): void
    {
        $this->assertSame(0.0, round($this->calculator->calculate(
            timeData: range(0, 10),
            movingData: array_fill(0, 11, true),
            heartRateData: array_fill(0, 11, 100),
            velocityData: array_fill(0, 11, 3),
        ), 1));
    }

    public function testCalculateReturnsPositivePercentageForHeartRateDrift(): void
    {
        $this->assertSame(9.1, round($this->calculator->calculate(
            timeData: range(0, 10),
            movingData: array_fill(0, 11, true),
            heartRateData: [100, 100, 100, 100, 100, 100, 110, 110, 110, 110, 110],
            velocityData: array_fill(0, 11, 3),
        ), 1));
    }

    public function testCalculateExcludesZeroHeartRateSamples(): void
    {
        $this->assertSame(0.0, round($this->calculator->calculate(
            timeData: range(0, 10),
            movingData: array_fill(0, 11, true),
            heartRateData: [100, 100, 100, 0, 100, 100, 100, 100, 100, 100, 100],
            velocityData: array_fill(0, 11, 3),
        ), 1));
    }

    public function testCalculateIgnoresPausedTimeWhenSplittingHalves(): void
    {
        $this->assertSame(9.1, round($this->calculator->calculate(
            timeData: range(0, 20),
            movingData: [
                true, true, true, true, true, true,
                false, false, false, false, false, false, false, false, false, false,
                true, true, true, true, true,
            ],
            heartRateData: [
                100, 100, 100, 100, 100, 100,
                250, 250, 250, 250, 250, 250, 250, 250, 250, 250,
                110, 110, 110, 110, 110,
            ],
            velocityData: [
                3, 3, 3, 3, 3, 3,
                10, 10, 10, 10, 10, 10, 10, 10, 10, 10,
                3, 3, 3, 3, 3,
            ],
        ), 1));
    }

    public function testCalculateReturnsNullWhenUsableDataIsInsufficient(): void
    {
        $this->assertNull($this->calculator->calculate(
            timeData: range(0, 10),
            movingData: array_fill(0, 11, false),
            heartRateData: array_fill(0, 11, 100),
            velocityData: array_fill(0, 11, 3),
        ));
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->calculator = new AerobicDecouplingCalculator();
    }
}
