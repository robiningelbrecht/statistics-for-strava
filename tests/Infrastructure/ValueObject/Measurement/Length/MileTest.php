<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Length;

use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Mile;
use PHPUnit\Framework\TestCase;

class MileTest extends TestCase
{
    public function testToMetric(): void
    {
        $this->assertEquals(
            Kilometer::zero(),
            Mile::zero()->toMetric(),
        );
    }
}
