<?php

declare(strict_types=1);

namespace App\Domain\Activity;

enum WorldType: string
{
    case REAL_WORLD = 'realWorld';
    case ZWIFT = 'zwift';
    case ROUVY = 'rouvy';
    case MY_WHOOSH = 'myWhoosh';

    public static function fromDeviceAndActivityName(?string $deviceName, string $activityName): self
    {
        return match (true) {
            'zwift' === strtolower($deviceName ?? '') => self::ZWIFT,
            'rouvy' === strtolower($deviceName ?? '') => self::ROUVY,
            'mywhoosh' === strtolower($deviceName ?? '') => self::MY_WHOOSH,
            str_contains(strtolower($activityName), 'mywhoosh') => self::MY_WHOOSH,
            default => self::REAL_WORLD,
        };
    }
}
