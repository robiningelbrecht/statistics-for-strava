<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Training;

enum TrainingType: string
{
    case POLARIZED = 'Polarized';
    case PYRAMIDAL = 'Pyramidal';
    case THRESHOLD = 'Threshold';
    case HIIT = 'HIIT';
    case BASE = 'Base';
    case UNIQUE_OTHER = 'Unique/Other';
}
