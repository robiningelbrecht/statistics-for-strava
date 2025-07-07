<?php

declare(strict_types=1);

namespace App\Tests\Domain\Integration\Weather\OpenMeteo;

use App\Domain\Integration\Weather\OpenMeteo\OpenMeteo;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

class SpyOpenMeteo implements OpenMeteo
{
    public function getWeatherStats(Coordinate $coordinate, SerializableDateTime $date): array
    {
        return [];
    }
}
