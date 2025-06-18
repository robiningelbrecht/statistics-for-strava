<?php

declare(strict_types=1);

namespace App\Infrastructure\Geocoding\Nominatim;

use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Sleep;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final readonly class LiveNominatim implements Nominatim
{
    public function __construct(
        private Client $client,
        private Sleep $sleep,
    ) {
    }

    public function reverseGeocode(Coordinate $coordinate): Location
    {
        $response = $this->client->request(
            'GET',
            'https://nominatim.openstreetmap.org/reverse',
            [
                RequestOptions::HEADERS => [
                    'User-Agent' => 'Statistics for Strava App',
                ],
                RequestOptions::QUERY => [
                    'lat' => $coordinate->getLatitude()->toFloat(),
                    'lon' => $coordinate->getLongitude()->toFloat(),
                    'format' => 'json',
                ],
            ]
        );

        $response = Json::decode($response->getBody()->getContents());
        $this->sleep->sweetDreams(1);

        return Location::fromState($response['address'] ?? throw new CouldNotReverseGeocodeAddress());
    }
}
