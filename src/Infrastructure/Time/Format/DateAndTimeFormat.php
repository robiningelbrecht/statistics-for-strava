<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\Format;

final readonly class DateAndTimeFormat
{
    private function __construct(
        private DateFormat $dateFormatShort,
        private DateFormat $dateFormatNormal,
        private TimeFormat $timeFormat,
    ) {
    }

    /**
     * @param string|array<string, mixed> $legacyDateFormat
     */
    public static function create(
        string $dateFormatShort,
        string $dateFormatNormal,
        string|array $legacyDateFormat,
        int $timeFormat,
    ): self {
        if (is_string($legacyDateFormat)) {
            // The fact that this is still a string, means that it's a legacy format.
            // Let's convert them to the new ones.
            [$dateFormatShort, $dateFormatNormal] = match ($legacyDateFormat) {
                DateFormat::LEGACY_FORMAT_DAY_MONTH_YEAR => ['d-m-y', 'd-m-Y'],
                DateFormat::LEGACY_FORMAT_MONTH_DAY_YEAR => ['m-d-y', 'm-d-Y'],
                default => throw new \InvalidArgumentException(sprintf('Invalid date format "%s"', $legacyDateFormat)),
            };
        }

        return new self(
            dateFormatShort: DateFormat::from($dateFormatShort),
            dateFormatNormal: DateFormat::from($dateFormatNormal),
            timeFormat: TimeFormat::from($timeFormat)
        );
    }

    public function getDateFormatShort(): DateFormat
    {
        return $this->dateFormatShort;
    }

    public function getDateFormatNormal(): DateFormat
    {
        return $this->dateFormatNormal;
    }

    public function getTimeFormat(): TimeFormat
    {
        return $this->timeFormat;
    }
}
