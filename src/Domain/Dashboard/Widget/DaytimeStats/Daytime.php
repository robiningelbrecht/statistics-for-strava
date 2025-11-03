<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\DaytimeStats;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum Daytime: string implements TranslatableInterface
{
    case MORNING = 'Morning';
    case AFTERNOON = 'Afternoon';
    case EVENING = 'Evening';
    case NIGHT = 'Night';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::MORNING => $translator->trans('Morning', locale: $locale),
            self::AFTERNOON => $translator->trans('Afternoon', locale: $locale),
            self::EVENING => $translator->trans('Evening', locale: $locale),
            self::NIGHT => $translator->trans('Night', locale: $locale),
        };
    }

    public static function fromSerializableDateTime(SerializableDateTime $dateTime): self
    {
        $hour = $dateTime->getHourWithoutLeadingZero();

        return match (true) {
            $hour >= 6 && $hour < 12 => self::MORNING, //  6 - 12
            $hour >= 12 && $hour < 17 => self::AFTERNOON, // 12 - 17
            $hour >= 17 && $hour < 23 => self::EVENING, //  17 -23
            $hour >= 23,
            $hour >= 0 => self::NIGHT, // 0 - 6,
            default => throw new \RuntimeException('Could not determine daytime'),
        };
    }

    public function getEmoji(): string
    {
        return match ($this) {
            self::MORNING => '🌞',
            self::AFTERNOON => '🌆',
            self::EVENING => '🌃',
            self::NIGHT => '🌙',
        };
    }

    /**
     * @return array<int, int>
     */
    public function getHours(): array
    {
        return match ($this) {
            self::MORNING => [6, 12],
            self::AFTERNOON => [12, 17],
            self::EVENING => [17, 23],
            self::NIGHT => [23, 6],
        };
    }
}
