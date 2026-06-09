<?php

declare(strict_types=1);

namespace App\Domain\Import\FileParser\Fit;

use App\Domain\Activity\SportType\SportType;

final class FitSportType
{
    private const int SPORT_RUNNING = 1;
    private const int SPORT_CYCLING = 2;
    private const int SPORT_SWIMMING = 5;
    private const int SPORT_WALKING = 11;
    private const int SPORT_ROWING = 15;
    private const int SPORT_HIKING = 17;
    private const int SPORT_E_BIKING = 21;

    private const int SUB_SPORT_TREADMILL = 1;
    private const int SUB_SPORT_TRAIL = 3;
    private const int SUB_SPORT_SPIN = 5;
    private const int SUB_SPORT_INDOOR_CYCLING = 6;
    private const int SUB_SPORT_MOUNTAIN = 8;
    private const int SUB_SPORT_DOWNHILL = 9;
    private const int SUB_SPORT_CYCLOCROSS = 11;
    private const int SUB_SPORT_E_BIKE_FITNESS = 28;
    private const int SUB_SPORT_INDOOR_RUNNING = 45;
    private const int SUB_SPORT_GRAVEL_CYCLING = 46;
    private const int SUB_SPORT_E_BIKE_MOUNTAIN = 47;
    private const int SUB_SPORT_VIRTUAL_ACTIVITY = 58;

    public static function resolve(?int $sport, ?int $subSport): ?SportType
    {
        return match (true) {
            self::SPORT_RUNNING === $sport && self::SUB_SPORT_TRAIL === $subSport => SportType::TRAIL_RUN,
            self::SPORT_RUNNING === $sport && in_array($subSport, [self::SUB_SPORT_TREADMILL, self::SUB_SPORT_INDOOR_RUNNING, self::SUB_SPORT_VIRTUAL_ACTIVITY], true) => SportType::VIRTUAL_RUN,
            self::SPORT_RUNNING === $sport => SportType::RUN,
            self::SPORT_CYCLING === $sport && in_array($subSport, [self::SUB_SPORT_MOUNTAIN, self::SUB_SPORT_DOWNHILL, self::SUB_SPORT_CYCLOCROSS], true) => SportType::MOUNTAIN_BIKE_RIDE,
            self::SPORT_CYCLING === $sport && self::SUB_SPORT_GRAVEL_CYCLING === $subSport => SportType::GRAVEL_RIDE,
            self::SPORT_CYCLING === $sport && in_array($subSport, [self::SUB_SPORT_INDOOR_CYCLING, self::SUB_SPORT_SPIN, self::SUB_SPORT_VIRTUAL_ACTIVITY], true) => SportType::VIRTUAL_RIDE,
            self::SPORT_CYCLING === $sport && in_array($subSport, [self::SUB_SPORT_E_BIKE_FITNESS, self::SUB_SPORT_E_BIKE_MOUNTAIN], true),self::SPORT_E_BIKING === $sport => SportType::E_BIKE_RIDE,
            self::SPORT_CYCLING === $sport => SportType::RIDE,
            self::SPORT_WALKING === $sport => SportType::WALK,
            self::SPORT_HIKING === $sport => SportType::HIKE,
            self::SPORT_SWIMMING === $sport => SportType::SWIM,
            self::SPORT_ROWING === $sport => SportType::ROWING,
            default => null,
        };
    }
}
