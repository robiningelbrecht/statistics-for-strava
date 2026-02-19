<?php

namespace App\Domain\Activity\Stream;

enum StreamType: string
{
    case TIME = 'time';
    case DISTANCE = 'distance';
    case LAT_LNG = 'latlng';
    case ALTITUDE = 'altitude';
    case VELOCITY = 'velocity_smooth';
    case HEART_RATE = 'heartrate';
    case CADENCE = 'cadence';
    case WATTS = 'watts';
    case TEMP = 'temp';
    case MOVING = 'moving';
    case GRADE = 'grade_smooth';

    /**
     * @return StreamType[]
     */
    public static function thatSupportDistributionValues(): array
    {
        return [
            self::WATTS, self::HEART_RATE, self::VELOCITY,
        ];
    }
}
