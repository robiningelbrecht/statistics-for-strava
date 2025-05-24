<?php

namespace App\Tests\Infrastructure\Twig;

use App\Infrastructure\Time\Format\DateAndTimeFormat;
use App\Infrastructure\Time\Format\DateFormat;
use App\Infrastructure\Time\Format\TimeFormat;
use App\Infrastructure\Twig\FormatDateAndTimeTwigExtension;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FormatDateAndTimeTwigExtensionTest extends TestCase
{
    #[DataProvider(methodName: 'provideDates')]
    public function testFormatDate(string $expectedFormattedDateString, SerializableDateTime $date, string $formatType, DateFormat $dateFormat): void
    {
        $extension = new FormatDateAndTimeTwigExtension(DateAndTimeFormat::create(
            dateFormat: $dateFormat->value,
            timeFormat: TimeFormat::AM_PM->value,
        ));

        $this->assertEquals(
            $expectedFormattedDateString,
            $extension->formatDate($date, $formatType)
        );
    }

    #[DataProvider(methodName: 'provideTimes')]
    public function testFormatTime(string $expectedFormattedTimeString, SerializableDateTime $date, TimeFormat $timeFormat): void
    {
        $extension = new FormatDateAndTimeTwigExtension(DateAndTimeFormat::create(
            dateFormat: DateFormat::DAY_MONTH_YEAR->value,
            timeFormat: $timeFormat->value,
        ));

        $this->assertEquals(
            $expectedFormattedTimeString,
            $extension->formatTime($date)
        );
    }

    public function testFormatDateItShouldThrow(): void
    {
        $extension = new FormatDateAndTimeTwigExtension(DateAndTimeFormat::create(
            dateFormat: DateFormat::DAY_MONTH_YEAR->value,
            timeFormat: TimeFormat::AM_PM->value,
        ));

        $this->expectExceptionObject(new \InvalidArgumentException('invalid'));
        $extension->formatDate(SerializableDateTime::fromString('31-01-2025'), 'invalid');
    }

    public static function provideDates(): array
    {
        return [
            ['31-01-25', SerializableDateTime::fromString('31-01-2025'), 'short', DateFormat::DAY_MONTH_YEAR],
            ['31-01-2025', SerializableDateTime::fromString('31-01-2025'), 'normal', DateFormat::DAY_MONTH_YEAR],
            ['01-31-25', SerializableDateTime::fromString('31-01-2025'), 'short', DateFormat::MONTH_DAY_YEAR],
            ['01-31-2025', SerializableDateTime::fromString('31-01-2025'), 'normal', DateFormat::MONTH_DAY_YEAR],
        ];
    }

    public static function provideTimes(): array
    {
        return [
            ['23:53', SerializableDateTime::fromString('31-01-2025 23:53'), TimeFormat::TWENTY_FOUR],
            ['11:53 pm', SerializableDateTime::fromString('31-01-2025 23:53'), TimeFormat::AM_PM],
            ['11:53 am', SerializableDateTime::fromString('31-01-2025 11:53'), TimeFormat::AM_PM],
        ];
    }
}
