<?php

declare(strict_types=1);

namespace App\Domain\Activity;

enum ActivityVisibility: string
{
    case EVERYONE = 'everyone';
    case FOLLOWERS_ONLY = 'followers_only';
    case ONLY_ME = 'only_me';
}
