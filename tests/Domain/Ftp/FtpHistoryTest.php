<?php

namespace App\Tests\Domain\Ftp;

use App\Domain\Ftp\FtpHistory;
use PHPUnit\Framework\TestCase;

class FtpHistoryTest extends TestCase
{
    public function testItShouldThrowOnInvalidDate(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid date "YYYY-MM-DD" set for athlete ftpHistory in config.yaml file'));
        FtpHistory::fromArray(['YYYY-MM-DD' => 220]);
    }
}
