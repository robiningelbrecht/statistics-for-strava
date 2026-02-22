<?php

namespace App\Tests\Infrastructure\Time\ResourceUsage;

use App\Infrastructure\Time\ResourceUsage\Timer;
use PHPUnit\Framework\TestCase;

class TimerTest extends TestCase
{
    public function testGetRunTimeInSeconds(): void
    {
        $timer = new Timer();

        $timer->start();
        usleep(100);
        $timer->stop();

        $this->assertEquals(
            0.001,
            $timer->getRunTimeInSeconds()
        );
    }

    public function testItShouldThrowWhenTimerIsNotStarted(): void
    {
        $timer = new Timer();

        $this->expectExceptionObject(new \RuntimeException('Timer not started'));
        $timer->getRunTimeInSeconds();
    }

    public function testItShouldThrowWhenTimerIsNotStopped(): void
    {
        $timer = new Timer();
        $timer->start();

        $this->expectExceptionObject(new \RuntimeException('Timer not stopped'));
        $timer->getRunTimeInSeconds();
    }
}
