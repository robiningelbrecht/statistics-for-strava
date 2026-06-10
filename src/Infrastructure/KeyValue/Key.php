<?php

declare(strict_types=1);

namespace App\Infrastructure\KeyValue;

enum Key: string
{
    case ATHLETE = 'athlete';
    case THEME = 'theme';
    case APP_LAST_BUILT_ON = 'appLastBuiltOn';
}
