<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Infrastructure\Time\Format\DateAndTimeFormat;
use App\Infrastructure\Time\Format\TimeFormat;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Carbon\CarbonInterval;
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
        $dateFormat = match ($formatType) {
            'short' => $this->dateAndTimeFormat->getDateFormatShort(),
            'normal' => $this->dateAndTimeFormat->getDateFormatNormal(),
            default => throw new \InvalidArgumentException(sprintf('Invalid date formatType "%s"', $formatType)),
        };

        return $date->translatedFormat((string) $dateFormat);
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

    #[AsTwigFilter('formatSeconds')]
    public function formatSeconds(Seconds $seconds): string
    {
        return CarbonInterval::seconds($seconds->toInt())->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
    }
}
