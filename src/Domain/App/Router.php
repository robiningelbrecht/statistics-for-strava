<?php

declare(strict_types=1);

namespace App\Domain\App;

enum Router
{
    case SYMFONY;
    case SINGLE_PAGE;
}
