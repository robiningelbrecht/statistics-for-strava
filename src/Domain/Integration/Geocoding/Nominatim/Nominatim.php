<?php

declare(strict_types=1);

namespace App\Domain\Integration\Geocoding\Nominatim;

use App\Infrastructure\ValueObject\Geography\Coordinate;

interface Nominatim
{
    /**
     * @return array<string, mixed>
     */
    public function reverseGeocode(Coordinate $coordinate): array;
}
