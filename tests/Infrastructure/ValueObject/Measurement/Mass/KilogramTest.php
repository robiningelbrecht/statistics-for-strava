<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Mass;

use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;
use App\Infrastructure\ValueObject\Measurement\Mass\Pound;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
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

    public function testToUnitSystem(): void
    {
        $this->assertEquals(
            Kilogram::zero(),
            Kilogram::zero()->toUnitSystem(UnitSystem::METRIC),
        );

        $this->assertEquals(
            Pound::zero(),
            Kilogram::zero()->toUnitSystem(UnitSystem::IMPERIAL),
        );
    }
}
