<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Velocity;

use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerMile;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class SecPerMileTest extends TestCase
{
    #[TestWith(data: [300, 186.4113])]
    #[TestWith(data: [0, 0])]
    public function testToMetric(int $valueToConvert, float $expectedResult): void
    {
        $this->assertEquals(
            SecPerKm::from($expectedResult),
            SecPerMile::from($valueToConvert)->toMetric()
        );
        $this->assertEquals(
            SecPerKm::from($expectedResult),
            SecPerMile::from($valueToConvert)->toSecPerKm()
        );
    }
}
