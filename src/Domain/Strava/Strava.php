<?php

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Challenge\ImportChallenges\ImportChallengesCommandHandler;
use App\Domain\Strava\Gear\GearId;
use App\Infrastructure\Logging\Monolog;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Sleep;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use League\Flysystem\FilesystemOperator;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;

#[WithMonologChannel('strava-api')]
class Strava
{
    private static ?string $cachedAccessToken = null;
    /** @var array<mixed>|null */
    private static ?array $cachedActivitiesResponse = null;

    public function __construct(
        private readonly Client $client,
        private readonly StravaClientId $stravaClientId,
        private readonly StravaClientSecret $stravaClientSecret,
        private readonly StravaRefreshToken $stravaRefreshToken,
        private readonly FilesystemOperator $filesystemOperator,
        private readonly Sleep $sleep,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<mixed> $options
     */
    private function request(
        string $path,
        string $method = 'GET',
        array $options = []): string
    {
        $options = array_merge([
            'base_uri' => 'https://www.strava.com/',
        ], $options);

        if ('GET' === $method) {
            // Try to avoid Strava rate limits.
            $this->sleep->sweetDreams(10);
        }

        $response = $this->client->request($method, $path, $options);

        $this->logger->info(new Monolog(
            $method,
            $path,
            'x-ratelimit-limit: '.$response->getHeaderLine('x-ratelimit-limit'),
            'x-ratelimit-usage: '.$response->getHeaderLine('x-ratelimit-usage'),
            'x-readratelimit-limit: '.$response->getHeaderLine('x-readratelimit-limit'),
            'x-readratelimit-usage: '.$response->getHeaderLine('x-readratelimit-usage'),
        ));

        return $response->getBody()->getContents();
    }

    private function getAccessToken(): string
    {
        if (!is_null(Strava::$cachedAccessToken)) {
            return Strava::$cachedAccessToken;
        }

        $response = $this->request('oauth/token', 'POST', [
            RequestOptions::FORM_PARAMS => [
                'client_id' => (string) $this->stravaClientId,
                'client_secret' => (string) $this->stravaClientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => (string) $this->stravaRefreshToken,
            ],
        ]);

        Strava::$cachedAccessToken = Json::decode($response)['access_token'] ?? throw new \RuntimeException('Could not fetch Strava accessToken');

        return Strava::$cachedAccessToken;
    }

    /**
     * @return array<mixed>
     */
    public function getAthlete(): array
    {
        return Json::decode($this->request('api/v3/athlete', 'GET', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
        ]));
    }

    /**
     * @return array<mixed>
     */
    public function getActivities(): array
    {
        if (!is_null(Strava::$cachedActivitiesResponse)) {
            return Strava::$cachedActivitiesResponse;
        }

        Strava::$cachedActivitiesResponse = [];

        $page = 1;
        do {
            $activities = Json::decode($this->request('api/v3/athlete/activities', 'GET', [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$this->getAccessToken(),
                ],
                RequestOptions::QUERY => [
                    'page' => $page,
                    'per_page' => 200,
                ],
            ]));

            Strava::$cachedActivitiesResponse = array_merge(
                Strava::$cachedActivitiesResponse,
                $activities
            );
            ++$page;
        } while (count($activities) > 0);

        return Strava::$cachedActivitiesResponse;
    }

    /**
     * @return array<mixed>
     */
    public function getActivity(ActivityId $activityId): array
    {
        return Json::decode($this->request('api/v3/activities/'.$activityId->toUnprefixedString(), 'GET', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
        ]));
    }

    /**
     * @return array<mixed>
     */
    public function getActivityZones(ActivityId $activityId): array
    {
        return Json::decode($this->request('api/v3/activities/'.$activityId->toUnprefixedString().'/zones', 'GET', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
        ]));
    }

    /**
     * @return array<mixed>
     */
    public function getAllActivityStreams(ActivityId $activityId): array
    {
        return Json::decode($this->request('api/v3/activities/'.$activityId->toUnprefixedString().'/streams', 'GET', [
            RequestOptions::QUERY => [
                'keys' => implode(',', array_map(fn (StreamType $streamType) => $streamType->value, StreamType::cases())),
            ],
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
        ]));
    }

    /**
     * @return array<mixed>
     */
    public function getActivityPhotos(ActivityId $activityId): array
    {
        return Json::decode($this->request('api/v3/activities/'.$activityId->toUnprefixedString().'/photos', 'GET', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
            RequestOptions::QUERY => [
                'size' => 5000,
            ],
        ]));
    }

    /**
     * @return array<mixed>
     */
    public function getGear(GearId $gearId): array
    {
        return Json::decode($this->request('api/v3/gear/'.$gearId->toUnprefixedString(), 'GET', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
        ]));
    }

    /**
     * @return array<mixed>
     */
    public function getChallengesOnPublicProfile(string $athleteId): array
    {
        $contents = $this->request('athletes/'.$athleteId);
        if (!preg_match_all('/<li class="Trophies_listItem[\S]*">(?<matches>[\s\S]*)<\/li>/U', $contents, $matches)) {
            throw new \RuntimeException('Could not fetch Strava challenges on public profile');
        }

        $challenges = [];
        foreach ($matches['matches'] as $match) {
            if (!preg_match('/<h4[\s\S]*>(?<match>.*?)<\/h4>/U', $match, $challengeName)) {
                throw new \RuntimeException('Could not fetch Strava challenge name');
            }
            if (!preg_match('/<a href="[\S]*" title="(?<match>.*?)" class="[\S]*"[\s\S]*\/>/U', $match, $teaser)) {
                throw new \RuntimeException('Could not fetch Strava challenge teaser');
            }
            if (!preg_match('/<img src="(?<match>.*?)" alt="[\s\S]*"[\s\S]*\/>/U', $match, $logoUrl)) {
                throw new \RuntimeException('Could not fetch Strava challenge logoUrl');
            }
            if (!preg_match('/<a href="\/challenges\/(?<match>.*?)" title="[\s\S]*"[\s\S]*>/U', $match, $url)) {
                throw new \RuntimeException('Could not fetch Strava challenge url');
            }
            if (!preg_match('/<img src="https[\S]+\/challenges\/(?<match>.*?)\/[\S]+.png" alt="[\s\S]*"[\s\S]*\/>/U', $match, $challengeId)) {
                // Apparently public profiles can contain challenges that we cannot process
                // because of missing required id. Skip these instead of throwing and aborting possible import.
                continue;
            }
            if (!preg_match('/<time[\s\S]*>(?<match>.*?)<\/time>/', $match, $completedOn)) {
                throw new \RuntimeException('Could not fetch Strava challenge timestamp');
            }
            if (empty(trim($completedOn['match']))) {
                throw new \RuntimeException('Could not fetch Strava challenge timestamp');
            }

            $challenges[] = [
                'name' => $challengeName['match'],
                'completedOn' => SerializableDateTime::createFromFormat('d M Y H:i:s', '01 '.trim($completedOn['match'].' 00:00:00')),
                'teaser' => $teaser['match'],
                'logo_url' => $logoUrl['match'],
                'url' => $url['match'],
                'challenge_id' => $challengeId['match'],
            ];
        }

        return $challenges;
    }

    /**
     * @return array<mixed>
     */
    public function getChallengesOnTrophyCase(): array
    {
        if (!$this->filesystemOperator->fileExists('storage/files/strava-challenge-history.html')) {
            return [];
        }
        $contents = $this->filesystemOperator->read('storage/files/strava-challenge-history.html');
        if (ImportChallengesCommandHandler::DEFAULT_STRAVA_CHALLENGE_HISTORY == trim($contents)) {
            return [];
        }
        if (!preg_match_all('/<ul class=\'list-block-grid list-trophies\'>(?<matches>[\s\S]*)<\/ul>/U', $contents, $matches)) {
            throw new \RuntimeException('Could not fetch Strava challenges from trophy case');
        }
        if (!preg_match_all('/<li(?<matches>[\s\S]*)<\/li>/U', $matches['matches'][0], $matches)) {
            throw new \RuntimeException('Could not fetch Strava challenges from trophy case');
        }

        $challenges = [];
        foreach ($matches['matches'] as $match) {
            $match = str_replace(["\r", "\n"], '', $match);
            if (!preg_match('/<a[\s\S]*>(?<match>.*?)<\/a><\/h6>/', $match, $challengeName)) {
                throw new \RuntimeException('Could not fetch Strava challenge name');
            }
            if (!preg_match('/class=\'centered\'[\s\S]*title=\'(?<match>.*?)\'>/', $match, $teaser)) {
                throw new \RuntimeException('Could not fetch Strava challenge teaser');
            }
            if (!preg_match('/<img[\s\S]* src="(?<match>.*?)"/', $match, $logoUrl)) {
                throw new \RuntimeException('Could not fetch Strava challenge logoUrl');
            }
            if (!preg_match('/<a str-on="click" [\s\S]*href="\/challenges\/(?<match>.*?)"[\s\S]*<\/a>/', $match, $url)) {
                throw new \RuntimeException('Could not fetch Strava challenge url');
            }
            if (!preg_match('/<img[\s\S]*data-trophy-challenge-id="(?<match>.*?)" src="[\s\S]*"[\s\S]*\/>/', $match, $challengeId)) {
                throw new \RuntimeException('Could not fetch Strava challenge challengeId');
            }
            if (!preg_match('/<time class=\'timestamp\'>(?<match>.*?)<\/time>/', $match, $completedOn)) {
                throw new \RuntimeException('Could not fetch Strava challenge timestamp');
            }
            if (empty(trim($completedOn['match']))) {
                throw new \RuntimeException('Could not fetch Strava challenge timestamp');
            }

            $challenges[] = [
                'completedOn' => SerializableDateTime::createFromFormat('d M Y H:i:s', '01 '.trim($completedOn['match']).' 00:00:00'),
                'name' => $challengeName['match'],
                'teaser' => $teaser['match'],
                'logo_url' => $logoUrl['match'],
                'url' => $url['match'],
                'challenge_id' => $challengeId['match'],
            ];
        }

        return $challenges;
    }

    public function downloadImage(string $uri): string
    {
        $response = $this->client->request('GET', $uri);

        return $response->getBody()->getContents();
    }
}
