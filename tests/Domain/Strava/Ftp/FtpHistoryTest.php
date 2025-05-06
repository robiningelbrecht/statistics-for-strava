<?php

namespace App\Tests\Domain\Strava\Ftp;

use App\Domain\Strava\Ftp\FtpHistory;
use PHPUnit\Framework\TestCase;

class FtpHistoryTest extends TestCase
{
    public function testItShouldThrow(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid FTP_HISTORY detected in .env file. Make sure the string is valid JSON'));
        FtpHistory::fromString('{"lol}');
    }

    public function testItShouldThrowOnInvalidDate(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid date "YYYY-MM-DD" set in FTP_HISTORY in .env file'));
        FtpHistory::fromString('{"YYYY-MM-DD": 220}');
    }
}
