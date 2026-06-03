<?php

declare(strict_types=1);

namespace App\Domain\Activity;

enum ImportSource: string
{
    case STRAVA_API = 'stravaApi';
    case FIT_FILE = 'fitFile';
    case TCX_FILE = 'tcxFile';
    case GPX_FILE = 'gpxFile';
}
