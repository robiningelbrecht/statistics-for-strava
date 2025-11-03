<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Time;

use App\Infrastructure\ValueObject\Measurement\Time\Minute;
use PHPUnit\Framework\TestCase;

class MinuteTest extends TestCase
{
    public function testGetSymbol(): void
    {
        $this->assertEquals(
            'min',
            Minute::zero()->getSymbol()
        );
    }
}
