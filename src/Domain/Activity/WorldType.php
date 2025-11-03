<?php

declare(strict_types=1);

namespace App\Domain\Activity;

enum WorldType: string
{
    case REAL_WORLD = 'realWorld';
    case ZWIFT = 'zwift';
    case ROUVY = 'rouvy';
    case MY_WHOOSH = 'myWhoosh';
}
