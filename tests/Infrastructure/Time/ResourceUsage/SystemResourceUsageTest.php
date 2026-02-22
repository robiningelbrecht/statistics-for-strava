<?php

namespace App\Tests\Infrastructure\Time\ResourceUsage;

use App\Infrastructure\Time\ResourceUsage\SystemResourceUsage;
use PHPUnit\Framework\TestCase;

class SystemResourceUsageTest extends TestCase
{
    public function testFormat(): void
    {
        $resourceUsage = new SystemResourceUsage();

        $resourceUsage->startTimer();
        usleep(100);
        $resourceUsage->stopTimer();

        $this->assertMatchesRegularExpression(
            '/Time: (.*?)s, Memory: (.*?) MB, Peak Memory: (.*?) MB/',
            'Time: 0.001s, Memory: 22.00 MB, Peak Memory: 22.00 MB',
        );
    }

    public function testStopItShouldThrowWhenTimerIsNotStarted(): void
    {
        $resourceUsage = new SystemResourceUsage();

        $this->expectExceptionObject(new \RuntimeException('Timer not started'));
        $resourceUsage->stopTimer();
    }

    public function testFormatItShouldThrowWhenTimerIsNotStarted(): void
    {
        $resourceUsage = new SystemResourceUsage();

        $this->expectExceptionObject(new \RuntimeException('Timer not started'));
        $resourceUsage->format();
    }

    public function testGetRunTimeInSecondsItShouldThrowWhenTimerIsNotStarted(): void
    {
        $resourceUsage = new SystemResourceUsage();

        $this->expectExceptionObject(new \RuntimeException('Timer not started'));
        $resourceUsage->getRunTimeInSeconds();
    }
}
