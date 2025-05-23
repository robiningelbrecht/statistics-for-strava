<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\Format;

final readonly class DateAndTimeFormat
{
    private function __construct(
        private DateFormat $dateFormat,
        private TimeFormat $timeFormat,
    ) {
    }

    public static function create(
        string $dateFormat,
        int $timeFormat,
    ): self {
        return new self(
            dateFormat: DateFormat::from($dateFormat),
            timeFormat: TimeFormat::from($timeFormat)
        );
    }

    public function getDateFormat(): DateFormat
    {
        return $this->dateFormat;
    }

    public function getTimeFormat(): TimeFormat
    {
        return $this->timeFormat;
    }
}
