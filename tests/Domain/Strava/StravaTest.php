<?php

namespace App\Tests\Domain\Strava;

use App\Domain\Activity\ActivityId;
use App\Domain\Challenge\ImportChallenges\ImportChallengesCommandHandler;
use App\Domain\Gear\GearId;
use App\Domain\Segment\SegmentId;
use App\Domain\Strava\InsufficientStravaAccessTokenScopes;
use App\Domain\Strava\InvalidStravaAccessToken;
use App\Domain\Strava\RateLimit\StravaRateLimitHasBeenReached;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaClientId;
use App\Domain\Strava\StravaClientSecret;
use App\Domain\Strava\StravaRefreshToken;
use App\Infrastructure\Serialization\Json;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\Infrastructure\Time\Sleep\NullSleep;
use App\Tests\SpyOutput;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Spatie\Snapshots\MatchesSnapshots;

class StravaTest extends TestCase
{
    use MatchesSnapshots;

    private Strava $strava;

    private MockObject $client;
    private MockObject $filesystemOperator;
    private NullSleep $sleep;
    private LoggerInterface $logger;

    public function testVerifyAccessToken(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->logger
            ->expects($this->never())
            ->method('log');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/athlete/activities', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->strava->verifyAccessToken();
    }

    public function testVerifyAccessTokenWhenTheTokenIsInvalid(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->logger
            ->expects($this->never())
            ->method('log');

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willThrowException(RequestException::wrapException(
                new Request('GET', 'uri'),
                new \RuntimeException()
            ));

        $this->expectExceptionObject(new InvalidStravaAccessToken());

        $this->strava->verifyAccessToken();
    }

    public function testVerifyAccessTokenWhenTheTokenHasInsufficientScopes(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->logger
            ->expects($this->never())
            ->method('log');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/athlete/activities', $path);

                throw new RequestException(message: 'The error', request: new Request('GET', 'uri'), response: new Response(401, [], Json::encode(['error' => 'The error'])));
            });

        $this->expectExceptionObject(new InsufficientStravaAccessTokenScopes());

        $this->strava->verifyAccessToken();
    }

    public function testVerifyAccessTokenWhenARandomErrorIsThrown(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->logger
            ->expects($this->never())
            ->method('log');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/athlete/activities', $path);

                throw new \RuntimeException('Oh no');
            });

        $this->expectExceptionObject(new \RuntimeException('Oh no'));

        $this->strava->verifyAccessToken();
    }

    public function testGetWhenUnexpectedError(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('The error');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                throw new RequestException(message: 'The error', request: new Request('GET', 'uri'), response: new Response(500, [], Json::encode(['error' => 'The error'])));
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->strava->getAthlete();
    }

    public function testGetWhenTooManyRequestsButNoRateLimits(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('The error');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                throw new RequestException(message: 'The error', request: new Request('GET', 'uri'), response: new Response(429, [], Json::encode(['error' => 'The error'])));
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->strava->getAthlete();
    }

    public function testGetWhenTooManyRequestsDailyRateLimitExceeded(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->expectExceptionObject(StravaRateLimitHasBeenReached::dailyReadLimit());

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                throw new RequestException(message: 'The error', request: new Request('GET', 'uri'), response: new Response(429, ['x-ratelimit-limit' => '200,2000', 'x-ratelimit-usage' => '1,2', 'x-readratelimit-limit' => '100,1000', 'x-readratelimit-usage' => '99,1001'], Json::encode(['error' => 'The error'])));
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->strava->getAthlete();
    }

    public function testGetWhenTooManyRequestsFifteenMinuteRateLimitExceeded(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->expectExceptionObject(StravaRateLimitHasBeenReached::fifteenMinuteReadLimit(3));

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                throw new RequestException(message: 'The error', request: new Request('GET', 'uri'), response: new Response(429, ['x-ratelimit-limit' => '200,2000', 'x-ratelimit-usage' => '1,2', 'x-readratelimit-limit' => '100,1000', 'x-readratelimit-usage' => '101,998'], Json::encode(['error' => 'The error'])));
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->strava->getAthlete();
    }

    public function testGetFifteenRateLimitIsAboutToBeHit(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                return new Response(200, [
                    'x-ratelimit-limit' => '200,2000',
                    'x-ratelimit-usage' => '1,2',
                    'x-readratelimit-limit' => '100,1000',
                    'x-readratelimit-usage' => '99,98',
                ], Json::encode(['weight' => 68, 'id' => 10]));
            });

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $spyOutput = new SpyOutput();
        $this->strava->setConsoleOutput($spyOutput);
        $this->strava->getAthlete();

        $this->assertMatchesTextSnapshot((string) $spyOutput);

        $this->assertEquals(
            180,
            $this->sleep->getTotalSleptInSeconds(),
        );
    }

    public function testGetAthlete(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/athlete', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [
                    'x-ratelimit-limit' => '200,2000',
                    'x-ratelimit-usage' => '1,2',
                    'x-readratelimit-limit' => '100,1000',
                    'x-readratelimit-usage' => '0,0',
                ], Json::encode(['weight' => 68, 'id' => 10]));
            });

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->strava->getAthlete();
        $this->assertMatchesObjectSnapshot($this->strava->getRateLimit());
        $this->assertEquals(
            0,
            $this->sleep->getTotalSleptInSeconds(),
        );
    }

    public function testGetActivities(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/athlete/activities', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->strava->getActivities();
        // Test static cache.
        $this->strava->getActivities();
    }

    public function testGetActivity(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/activities/3', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->strava->getActivity(ActivityId::fromUnprefixed(3));
    }

    public function testGetActivityZones(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/activities/3/zones', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->strava->getActivityZones(ActivityId::fromUnprefixed(3));
    }

    public function testGetAllActivityStreams(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/activities/3/streams', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->strava->getAllActivityStreams(ActivityId::fromUnprefixed(3));
    }

    public function testGetAllActivityPhotos(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/activities/3/photos', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->strava->getActivityPhotos(ActivityId::fromUnprefixed(3));
    }

    public function testGetGear(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/gear/3', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->strava->getGear(GearId::fromUnprefixed(3));
    }

    public function testGetSegment(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/segments/3', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->strava->getSegment(SegmentId::fromUnprefixed(3));
    }

    public function testGetChallengesOnPublicProfile(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile.html'));
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $challenges = $this->strava->getChallengesOnPublicProfile('10');
        $this->assertMatchesJsonSnapshot($challenges);
    }

    public function testGetChallengesOnPublicProfileWhenInvalidProfile(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], '');
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenges on public profile'));

        $this->strava->getChallengesOnPublicProfile('10');
    }

    public function testGetChallengesOnPublicProfileWhenNameNotFound(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile-without-name.html'));
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge name'));

        $this->strava->getChallengesOnPublicProfile('10');
    }

    public function testGetChallengesOnPublicProfileWhenTeaserNotFound(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile-without-teaser.html'));
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge teaser'));

        $this->strava->getChallengesOnPublicProfile('10');
    }

    public function testGetChallengesOnPublicProfileWhenLogoNotFound(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile-without-logo.html'));
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge logoUrl'));

        $this->strava->getChallengesOnPublicProfile('10');
    }

    public function testGetChallengesOnPublicProfileWhenUrlNotFound(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile-without-url.html'));
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge url'));

        $this->strava->getChallengesOnPublicProfile('10');
    }

    public function testGetChallengesOnPublicProfileWhenIdNotFound(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile-without-id.html'));
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->strava->getChallengesOnPublicProfile('10');
    }

    public function testGetChallengesOnPublicProfileWhenTimeNotFound(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile-without-time.html'));
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge timestamp'));

        $this->strava->getChallengesOnPublicProfile('10');
    }

    public function testGetChallengesOnPublicProfileWhenTimeIsEmpty(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile-with-empty-time.html'));
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge timestamp'));

        $this->strava->getChallengesOnPublicProfile('10');
    }

    public function testGetChallengesOnTrophyCase(): void
    {
        $this->client
            ->expects($this->never())
            ->method('request');

        $this->filesystemOperator
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $this->filesystemOperator
            ->expects($this->once())
            ->method('read')
            ->with('storage/files/strava-challenge-history.html')
            ->willReturn(file_get_contents(__DIR__.'/trophy-case.html'));

        $this->logger
            ->expects($this->never())
            ->method('info');

        $challenges = $this->strava->getChallengesOnTrophyCase();
        $this->assertMatchesJsonSnapshot($challenges);
    }

    public function testGetChallengesOnTrophyCaseWhenFileNotFound(): void
    {
        $this->client
            ->expects($this->never())
            ->method('request');

        $this->filesystemOperator
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(false);

        $this->filesystemOperator
            ->expects($this->never())
            ->method('read');

        $this->logger
            ->expects($this->never())
            ->method('info');

        $challenges = $this->strava->getChallengesOnTrophyCase();
        $this->assertEmpty($challenges);
    }

    public function testGetChallengesOnTrophyCaseWithDefaultHtml(): void
    {
        $this->client
            ->expects($this->never())
            ->method('request');

        $this->filesystemOperator
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $this->filesystemOperator
            ->expects($this->once())
            ->method('read')
            ->with('storage/files/strava-challenge-history.html')
            ->willReturn(ImportChallengesCommandHandler::DEFAULT_STRAVA_CHALLENGE_HISTORY);

        $this->logger
            ->expects($this->never())
            ->method('info');

        $challenges = $this->strava->getChallengesOnTrophyCase();
        $this->assertEmpty($challenges);
    }

    public function testGetChallengesOnTrophyCaseWhenInvalidHtml(): void
    {
        $this->client
            ->expects($this->never())
            ->method('request');

        $this->filesystemOperator
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $this->filesystemOperator
            ->expects($this->once())
            ->method('read')
            ->with('storage/files/strava-challenge-history.html')
            ->willReturn('');

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenges from trophy case'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenInvalidHtmlCaseTwo(): void
    {
        $this->client
            ->expects($this->never())
            ->method('request');

        $this->filesystemOperator
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $this->filesystemOperator
            ->expects($this->once())
            ->method('read')
            ->with('storage/files/strava-challenge-history.html')
            ->willReturn("<ul class='list-block-grid list-trophies'>YEAHBABY</ul>");

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenges from trophy case'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenNameNotFound(): void
    {
        $this->client
            ->expects($this->never())
            ->method('request');

        $this->filesystemOperator
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $this->filesystemOperator
            ->expects($this->once())
            ->method('read')
            ->with('storage/files/strava-challenge-history.html')
            ->willReturn(file_get_contents(__DIR__.'/trophy-case-without-name.html'));

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge name'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenTeaserNotFound(): void
    {
        $this->client
            ->expects($this->never())
            ->method('request');

        $this->filesystemOperator
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $this->filesystemOperator
            ->expects($this->once())
            ->method('read')
            ->with('storage/files/strava-challenge-history.html')
            ->willReturn(file_get_contents(__DIR__.'/trophy-case-without-teaser.html'));

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge teaser'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenLogoNotFound(): void
    {
        $this->client
            ->expects($this->never())
            ->method('request');

        $this->filesystemOperator
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $this->filesystemOperator
            ->expects($this->once())
            ->method('read')
            ->with('storage/files/strava-challenge-history.html')
            ->willReturn(file_get_contents(__DIR__.'/trophy-case-without-logo.html'));

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge logoUrl'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenUrlNotFound(): void
    {
        $this->client
            ->expects($this->never())
            ->method('request');

        $this->filesystemOperator
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $this->filesystemOperator
            ->expects($this->once())
            ->method('read')
            ->with('storage/files/strava-challenge-history.html')
            ->willReturn(file_get_contents(__DIR__.'/trophy-case-without-url.html'));

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge url'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenIdNotFound(): void
    {
        $this->client
            ->expects($this->never())
            ->method('request');

        $this->filesystemOperator
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $this->filesystemOperator
            ->expects($this->once())
            ->method('read')
            ->with('storage/files/strava-challenge-history.html')
            ->willReturn(file_get_contents(__DIR__.'/trophy-case-without-id.html'));

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge challengeId'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenTimestampNotFound(): void
    {
        $this->client
            ->expects($this->never())
            ->method('request');

        $this->filesystemOperator
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $this->filesystemOperator
            ->expects($this->once())
            ->method('read')
            ->with('storage/files/strava-challenge-history.html')
            ->willReturn(file_get_contents(__DIR__.'/trophy-case-without-timestamp.html'));

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge timestamp'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWithEmptyTimestamp(): void
    {
        $this->client
            ->expects($this->never())
            ->method('request');

        $this->filesystemOperator
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $this->filesystemOperator
            ->expects($this->once())
            ->method('read')
            ->with('storage/files/strava-challenge-history.html')
            ->willReturn(file_get_contents(__DIR__.'/trophy-case-with-empty-timestamp.html'));

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge timestamp'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testDownloadImage(): void
    {
        $this->filesystemOperator
            ->expects($this->never())
            ->method('fileExists');

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('uri', $path);

                return new Response(200, [], '');
            });

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->strava->downloadImage('uri');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Client::class);
        $this->filesystemOperator = $this->createMock(FilesystemOperator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->strava = new Strava(
            client: $this->client,
            stravaClientId: StravaClientId::fromString('clientId'),
            stravaClientSecret: StravaClientSecret::fromString('clientSecret'),
            stravaRefreshToken: StravaRefreshToken::fromString('refreshToken'),
            filesystemOperator: $this->filesystemOperator,
            sleep: $this->sleep = new NullSleep(),
            logger: $this->logger,
            clock: PausedClock::fromString('2025-11-02 12:43:20')
        );
        $this->strava::$cachedAccessToken = null;
        $this->strava::$cachedActivitiesResponse = null;
    }
}
