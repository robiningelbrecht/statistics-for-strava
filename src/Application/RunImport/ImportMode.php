<?php

declare(strict_types=1);

namespace App\Application\RunImport;

enum ImportMode: string
{
    case STRAVA_API = 'stravaApi';
    case FILE = 'file';

    public function isStravaApi(): bool
    {
        return self::STRAVA_API === $this;
    }

    public function isFile(): bool
    {
        return self::FILE === $this;
    }
}
