<?php

declare(strict_types=1);

namespace App\Domain\Activity;

interface ActivityIdFactory
{
    public function random(): ActivityId;
}
