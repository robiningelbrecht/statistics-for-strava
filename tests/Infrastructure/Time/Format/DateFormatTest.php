<?php

namespace App\Tests\Infrastructure\Time\Format;

use App\Infrastructure\Time\Format\DateFormat;
use PHPUnit\Framework\TestCase;

class DateFormatTest extends TestCase
{
    public function testFrom(): void
    {
        $this->assertEquals(
            'DD., dd.MM.yy',
            (string) DateFormat::from('DD., dd.MM.yy')
        );
    }

    public function testFromItShouldThrowWhenEmpty(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid date format provided. Format cannot be empty'));
        DateFormat::from('');
    }

    public function testFromItShouldThrowInvalidFormat(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid date format provided "EE RR b", invalid format characters found: E, R, b'));
        DateFormat::from('EE RR b');
    }
}
