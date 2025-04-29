<?php

namespace App\Tests\Domain\Zwift;

use App\Domain\Zwift\ZwiftLevel;
use PHPUnit\Framework\TestCase;

class ZwiftLevelTest extends TestCase
{
    public function testLevel(): void
    {
        $this->assertEquals(1, ZwiftLevel::fromInt(1)->getValue());
        $this->assertEquals(100, ZwiftLevel::fromInt(100)->getValue());
    }

    public function testGetProgress(): void
    {
        $this->assertEquals(
            1.0,
            ZwiftLevel::fromInt(1)->getProgressPercentage()
        );
        $this->assertEquals(
            100,
            ZwiftLevel::fromInt(100)->getProgressPercentage()
        );
        $this->assertEquals(
            96,
            ZwiftLevel::fromInt(99)->getProgressPercentage()
        );
        $this->assertEquals(
            66.17,
            ZwiftLevel::fromInt(80)->getProgressPercentage()
        );
    }

    public function testItShouldThrowWhenLevelTooLow(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('ZwiftLevel must be a number between 1 and 100'));

        ZwiftLevel::fromInt(0);
    }

    public function testItShouldThrowWhenLevelTooHigh(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('ZwiftLevel must be a number between 1 and 100'));

        ZwiftLevel::fromInt(101);
    }
}
