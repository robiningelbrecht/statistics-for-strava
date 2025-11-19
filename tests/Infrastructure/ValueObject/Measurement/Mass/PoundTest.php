<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Mass;

use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;
use App\Infrastructure\ValueObject\Measurement\Mass\Pound;
use PHPUnit\Framework\TestCase;

class PoundTest extends TestCase
{
    public function testToMetric(): void
    {
        $this->assertEquals(
            Kilogram::zero(),
            Pound::zero()->toMetric(),
        );
    }
}
