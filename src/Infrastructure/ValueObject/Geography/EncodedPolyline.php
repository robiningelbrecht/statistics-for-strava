<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Geography;

use App\Infrastructure\ValueObject\String\NonEmptyStringLiteral;

final readonly class EncodedPolyline extends NonEmptyStringLiteral
{
    private const int PRECISION = 5;

    public function getStartingCoordinate(): Coordinate
    {
        $encodedPolyline = (string) $this;
        $points = [];
        $index = $i = 0;
        $previous = [0, 0];

        while ($i < strlen($encodedPolyline) && count($points) < 2) {
            $shift = $result = 0x00;
            do {
                $bit = ord($encodedPolyline[$i++]) - 63;
                $result |= ($bit & 0x1F) << $shift;
                $shift += 5;
            } while ($bit >= 0x20);

            $diff = ($result & 1) ? ~($result >> 1) : ($result >> 1);
            $number = $previous[$index % 2] + $diff;
            $previous[$index % 2] = $number;
            $points[] = $number * 1 / 10 ** self::PRECISION;
            ++$index;
        }

        return Coordinate::createFromLatAndLng(
            latitude: Latitude::fromString((string) $points[0]),
            longitude: Longitude::fromString((string) $points[1]),
        );
    }

    /**
     * @return float[]
     */
    public function decode(): array
    {
        $encodedPolyline = (string) $this;
        $points = [];
        $index = $i = 0;
        $previous = [0, 0];
        while ($i < strlen($encodedPolyline)) {
            $shift = $result = 0x00;
            do {
                $bit = ord($encodedPolyline[$i++]) - 63;
                $result |= ($bit & 0x1F) << $shift;
                $shift += 5;
            } while ($bit >= 0x20);

            $diff = ($result & 1) ? ~($result >> 1) : ($result >> 1);
            $number = $previous[$index % 2] + $diff;
            $previous[$index % 2] = $number;
            ++$index;
            $points[] = $number * 1 / 10 ** self::PRECISION;
        }

        return $points;
    }

    /**
     * @return array<int, array<float, float>>
     */
    public function decodeAndPairLonLat(): array
    {
        return array_map(
            fn (array $pair) => [$pair[1], $pair[0]],
            array_chunk($this->decode(), 2)
        );
    }
}
