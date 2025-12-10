<?php

declare(strict_types=1);

namespace App\Domain\Integration\Geocoding\Nominatim;

use App\Domain\Activity\Route\RouteGeography;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Sleep;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

final readonly class LiveNominatim implements Nominatim
{
    public function __construct(
        private Client $client,
        private Sleep $sleep,
    ) {
    }

    public function reverseGeocode(Coordinate $coordinate): array
    {
        try {
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
        } catch (ConnectException|RequestException) {
            throw new CouldNotReverseGeocodeAddress();
        }

        if (!isset($response['address'])) {
            throw new CouldNotReverseGeocodeAddress();
        }

        return [
            ...$response['address'],
            RouteGeography::IS_REVERSE_GEOCODED => true,
        ];
    }
}
