<?php

declare(strict_types=1);

namespace App\Domain\Integration\GitHub;

use App\Infrastructure\Serialization\Json;
use GuzzleHttp\Client;

final readonly class GitHub
{
    public function __construct(
        private Client $client,
    ) {
    }

    public function getLatestRelease(): string
    {
        $response = $this->client->request(
            method: 'GET',
            uri: 'https://api.github.com/repos/robiningelbrecht/statistics-for-strava/releases/latest'
        );

        $json = $response->getBody()->getContents();

        return Json::decode($json)['name'];
    }
}
