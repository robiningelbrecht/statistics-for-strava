<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;

interface RewindRepository
{
    public function getAvailableRewindYears(SerializableDateTime $now): Years;
}
