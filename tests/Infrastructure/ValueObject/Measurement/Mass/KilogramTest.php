<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Mass;

use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;
use App\Infrastructure\ValueObject\Measurement\Mass\Pound;
use PHPUnit\Framework\TestCase;

class KilogramTest extends TestCase
{
    public function testToImperial(): void
    {
        $this->assertEquals(
            Pound::zero(),
            Kilogram::zero()->toImperial(),
        );
    }
}
