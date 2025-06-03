<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Infrastructure\Time\Format\DateAndTimeFormat;
use App\Infrastructure\Time\Format\DateFormat;
use App\Infrastructure\Time\Format\TimeFormat;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Attribute\AsTwigFilter;

final readonly class FormatDateAndTimeTwigExtension
{
    public function __construct(
        private DateAndTimeFormat $dateAndTimeFormat,
    ) {
    }

    #[AsTwigFilter('formatDate')]
    public function formatDate(SerializableDateTime $date, string $formatType = 'short'): string
    {
        $dateFormat = $this->dateAndTimeFormat->getDateFormat();

        return match ($dateFormat) {
            DateFormat::DAY_MONTH_YEAR => match ($formatType) {
                'short' => $date->format('d-m-y'),
                'normal' => $date->format('d-m-Y'),
                default => throw new \InvalidArgumentException(sprintf('Invalid date format type "%s"', $formatType)),
            },
            DateFormat::MONTH_DAY_YEAR => match ($formatType) {
                'short' => $date->format('m-d-y'),
                'normal' => $date->format('m-d-Y'),
                default => throw new \InvalidArgumentException(sprintf('Invalid date format type "%s"', $formatType)),
            },
        };
    }

    #[AsTwigFilter('formatTime')]
    public function formatTime(SerializableDateTime $date): string
    {
        $timeFormat = $this->dateAndTimeFormat->getTimeFormat();

        return match ($timeFormat) {
            TimeFormat::TWENTY_FOUR => $date->format('H:i'),
            TimeFormat::AM_PM => $date->format('h:i a'),
        };
    }
}
