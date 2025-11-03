<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Time;

use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use PHPUnit\Framework\TestCase;

class SecondsTest extends TestCase
{
    public function testGetSymbol(): void
    {
        $this->assertEquals(
            's',
            Seconds::zero()->getSymbol()
        );
    }
}
