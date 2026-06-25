<?php

declare(strict_types=1);

namespace App\Domain\Image;

enum ImageStatus: string
{
    case NEW = 'new';
    case UNCHANGED = 'unchanged';
    case REMOVED = 'removed';
}
