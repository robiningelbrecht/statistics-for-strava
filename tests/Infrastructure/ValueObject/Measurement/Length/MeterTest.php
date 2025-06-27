<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Length;

use App\Infrastructure\ValueObject\Measurement\Length\Foot;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use PHPUnit\Framework\TestCase;

class MeterTest extends TestCase
{
    public function testToUnitSystem(): void
    {
        $this->assertEquals(
            Meter::zero(),
            Meter::zero()->toUnitSystem(UnitSystem::METRIC),
        );

        $this->assertEquals(
            Foot::zero(),
            Meter::zero()->toUnitSystem(UnitSystem::IMPERIAL),
        );
    }
}
