<?php

declare(strict_types=1);

namespace App\Domain\Integration\Weather\OpenMeteo;

use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface OpenMeteo
{
    /**
     * @return array<mixed>
     */
    public function getWeatherStats(
        Coordinate $coordinate,
        SerializableDateTime $date,
    ): array;
}
