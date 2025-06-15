<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\HeartRateZone;

final readonly class HeartRateZone
{
    public const string ONE = 'zone1';
    public const string TWO = 'zone2';
    public const string THREE = 'zone3';
    public const string FOUR = 'zone4';
    public const string FIVE = 'zone5';

    public function __construct(
        private string $name,
        private int $from,
        private ?int $to,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrom(): int
    {
        return $this->from;
    }

    public function getTo(): ?int
    {
        return $this->to;
    }

    /**
     * @return array<int, int>
     */
    public function getRange(int $athleteMaxHeartRate, HeartRateZoneMode $mode): array
    {
        if (HeartRateZoneMode::ABSOLUTE === $mode) {
            return [$this->getFrom(), $this->getTo() ?? 10000];
        }

        if (0 === $athleteMaxHeartRate % 10) {
            return match ($this) {
                self::ONE => [0, (int) ($athleteMaxHeartRate * 0.6)],
                self::TWO => [(int) ($athleteMaxHeartRate * 0.6) + 1, (int) ($athleteMaxHeartRate * 0.7)],
                self::THREE => [(int) ($athleteMaxHeartRate * 0.7) + 1, (int) ($athleteMaxHeartRate * 0.8)],
                self::FOUR => [(int) ($athleteMaxHeartRate * 0.8) + 1, (int) ($athleteMaxHeartRate * 0.9)],
                self::FIVE => [(int) ($athleteMaxHeartRate * 0.9) + 1, 10000],
            };
        }

        return match ($this) {
            self::ONE => [0, (int) floor($athleteMaxHeartRate * 0.6)],
            self::TWO => [(int) ceil($athleteMaxHeartRate * 0.6), (int) floor($athleteMaxHeartRate * 0.7)],
            self::THREE => [(int) ceil($athleteMaxHeartRate * 0.7), (int) floor($athleteMaxHeartRate * 0.8)],
            self::FOUR => [(int) ceil($athleteMaxHeartRate * 0.8), (int) floor($athleteMaxHeartRate * 0.9)],
            self::FIVE => [(int) ceil($athleteMaxHeartRate * 0.9), 10000],
        };
    }
}
