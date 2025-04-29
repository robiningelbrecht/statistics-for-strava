<?php

namespace App\Tests\Domain\Strava\Ftp\ImportFtp;

use App\Domain\Strava\Ftp\ImportFtp\FtpHistoryFromEnvFile;
use PHPUnit\Framework\TestCase;

class FtpHistoryFromEnvFileTest extends TestCase
{
    public function testItShouldThrow(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid FTP_HISTORY detected in .env file. Make sure the string is valid JSON'));
        FtpHistoryFromEnvFile::fromString('{"lol}');
    }

    public function testItShouldThrowOnInvalidDate(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid date "YYYY-MM-DD" set in FTP_HISTORY in .env file'));
        FtpHistoryFromEnvFile::fromString('{"YYYY-MM-DD": 220}');
    }
}
