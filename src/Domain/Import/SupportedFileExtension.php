<?php

declare(strict_types=1);

namespace App\Domain\Import;

enum SupportedFileExtension: string
{
    case FIT = 'fit';
    case TCX = 'tcx';
    case GPX = 'gpx';
}
