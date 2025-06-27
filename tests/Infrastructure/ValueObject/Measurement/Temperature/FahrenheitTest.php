<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Temperature;

use App\Infrastructure\ValueObject\Measurement\Temperature\Celsius;
use App\Infrastructure\ValueObject\Measurement\Temperature\Fahrenheit;
use PHPUnit\Framework\TestCase;

class FahrenheitTest extends TestCase
{
    public function testToMetric(): void
    {
        $this->assertEquals(
            Celsius::from(-17.78),
            Fahrenheit::zero()->toMetric()
        );
    }
}
