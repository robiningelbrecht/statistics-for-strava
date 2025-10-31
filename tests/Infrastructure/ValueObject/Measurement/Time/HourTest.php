<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Time;

use App\Infrastructure\ValueObject\Measurement\Time\Hour;
use PHPUnit\Framework\TestCase;

class HourTest extends TestCase
{
    public function testGetSymbol(): void
    {
        $this->assertEquals(
            'h',
            Hour::zero()->getSymbol()
        );
    }
}
