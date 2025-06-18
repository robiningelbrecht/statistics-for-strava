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
    public function testFormatDate(string $expectedFormattedDateString, SerializableDateTime $date, string $formatType, string $shortDateFormat, string $normalDateFormat, string|array $legacyDateFormat): void
    {
        $extension = new FormatDateAndTimeTwigExtension(DateAndTimeFormat::create(
            dateFormatShort: $shortDateFormat,
            dateFormatNormal: $normalDateFormat,
            legacyDateFormat: $legacyDateFormat,
            timeFormat: TimeFormat::AM_PM->value,
        ));

        $this->assertEquals(
            $expectedFormattedDateString,
            $extension->formatDate($date, $formatType)
        );
    }

    public function testFormatDateItShouldThrow(): void
    {
        $extension = new FormatDateAndTimeTwigExtension(DateAndTimeFormat::create(
            dateFormatShort: 'd-m-y',
            dateFormatNormal: 'd-m-Y',
            legacyDateFormat: [],
            timeFormat: TimeFormat::AM_PM->value,
        ));

        $this->expectExceptionObject(new \InvalidArgumentException('Invalid date formatType "invalid"'));
        $extension->formatDate(SerializableDateTime::fromString('2025-01-01'), 'invalid');
    }

    #[DataProvider(methodName: 'provideTimes')]
    public function testFormatTime(string $expectedFormattedTimeString, SerializableDateTime $date, TimeFormat $timeFormat): void
    {
        $extension = new FormatDateAndTimeTwigExtension(DateAndTimeFormat::create(
            dateFormatShort: 'd-m-y',
            dateFormatNormal: 'd-m-Y',
            legacyDateFormat: [],
            timeFormat: $timeFormat->value,
        ));

        $this->assertEquals(
            $expectedFormattedTimeString,
            $extension->formatTime($date)
        );
    }

    public static function provideDates(): array
    {
        return [
            ['31-01-25', SerializableDateTime::fromString('31-01-2025'), 'short', 'd-m-y', 'd-m-Y', []],
            ['31-01-2025', SerializableDateTime::fromString('31-01-2025'), 'normal', 'd-m-y', 'd-m-Y', []],
            ['01-31-25', SerializableDateTime::fromString('31-01-2025'), 'short', 'm-d-y', 'm-d-Y', []],
            ['01-31-2025', SerializableDateTime::fromString('31-01-2025'), 'normal', 'm-d-y', 'm-d-Y', []],
            ['Fri., 31.01.25', SerializableDateTime::fromString('31-01-2025'), 'normal', 'D., d.m.y', 'D., d.m.y', []],
            ['31-01-25', SerializableDateTime::fromString('31-01-2025'), 'short', 'D., d.m.y', 'D., d.m.y', DateFormat::LEGACY_FORMAT_DAY_MONTH_YEAR],
            ['31-01-2025', SerializableDateTime::fromString('31-01-2025'), 'normal', 'D., d.m.y', 'D., d.m.y', DateFormat::LEGACY_FORMAT_DAY_MONTH_YEAR],
            ['01-31-25', SerializableDateTime::fromString('31-01-2025'), 'short', 'D., d.m.y', 'D., d.m.y', DateFormat::LEGACY_FORMAT_MONTH_DAY_YEAR],
            ['01-31-2025', SerializableDateTime::fromString('31-01-2025'), 'normal', 'D., d.m.y', 'D., d.m.y', DateFormat::LEGACY_FORMAT_MONTH_DAY_YEAR],
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
