<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Temperature;

use App\Infrastructure\ValueObject\Measurement\Temperature\Celsius;
use App\Infrastructure\ValueObject\Measurement\Temperature\Fahrenheit;
use PHPUnit\Framework\TestCase;

class CelsiusTest extends TestCase
{
    public function testToImperial(): void
    {
        $this->assertEquals(
            Fahrenheit::from(32),
            Celsius::zero()->toImperial()
        );
    }
}
