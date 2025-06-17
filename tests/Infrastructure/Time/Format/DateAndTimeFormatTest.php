<?php

namespace App\Tests\Infrastructure\Time\Format;

use App\Infrastructure\Time\Format\DateAndTimeFormat;
use App\Infrastructure\Time\Format\TimeFormat;
use PHPUnit\Framework\TestCase;

class DateAndTimeFormatTest extends TestCase
{
    public function testFormatDateItShouldThrowForInvalidLegacyFormat(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid date format "lol"'));
        DateAndTimeFormat::create(
            'd-m-y',
            'd-m-Y',
            'lol',
            TimeFormat::AM_PM->value
        );
    }
}
