<?php

namespace App\Tests\Domain\Strava\Ftp;

use App\Domain\Strava\Ftp\FtpHistory;
use PHPUnit\Framework\TestCase;

class FtpHistoryTest extends TestCase
{
    public function testItShouldThrowOnInvalidDate(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid date "YYYY-MM-DD" set for athlete ftpHistory in config.yaml file'));
        FtpHistory::fromArray(['YYYY-MM-DD' => 220]);
    }
}
