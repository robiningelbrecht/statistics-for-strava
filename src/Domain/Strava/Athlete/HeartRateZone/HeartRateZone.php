<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\HeartRateZone;

enum HeartRateZone: int
{
    case ONE = 1;
    case TWO = 2;
    case THREE = 3;
    case FOUR = 4;
    case FIVE = 5;

    /**
     * @return array<int, int>
     */
    public function getMinMaxRange(int $athleteMaxHeartRate): array
    {
        // Zone 1 (Recovery or very light intensity): 50-60% of MHR
        // Zone 2 (Aerobic or light intensity): 60-70% of MHR
        // Zone 3 (Aerobic/anaerobic or moderate intensity): 70-80% of MHR
        // Zone 4 (Anaerobic or hard intensity): 80-90% of MHR
        // Zone 5 (Maximal or very hard intensity): 90-100% of MHR

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
