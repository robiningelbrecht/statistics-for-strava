<?php

namespace App\Tests\Controller;

use App\Controller\AppRequestHandler;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaClientId;
use App\Domain\Strava\StravaClientSecret;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class AppRequestHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private AppRequestHandler $appRequestHandler;
    private MockObject $strava;
    private MockObject $client;

    public function testHandle(): void
    {
        /** @var \League\Flysystem\InMemory\InMemoryFilesystemAdapter $buildStorage */
        $buildStorage = $this->getContainer()->get('build.storage');
        $buildStorage->write('index.html', 'I am the index', []);

        $this->assertMatchesHtmlSnapshot($this->appRequestHandler->handle(new Request(
            query: [],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ))->getContent());
    }

    public function testHandleWhenInvalidRefreshToken(): void
    {
        $this->strava
            ->expects($this->once())
            ->method('getAthlete')
            ->willThrowException(RequestException::wrapException(
                new \GuzzleHttp\Psr7\Request('GET', 'uri'),
                new \RuntimeException()
            ));

        $this->assertMatchesHtmlSnapshot($this->appRequestHandler->handle(new Request(
            query: [],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ))->getContent());
    }

    public function testHandleWhenValidRefreshTokenButNotBuilt(): void
    {
        $this->assertMatchesHtmlSnapshot($this->appRequestHandler->handle(new Request(
            query: [],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ))->getContent());
    }

    public function testHandleOauthWithCode(): void
    {
        /** @var \League\Flysystem\InMemory\InMemoryFilesystemAdapter $buildStorage */
        $buildStorage = $this->getContainer()->get('build.storage');
        $buildStorage->write('index.html', 'I am the index', []);

        $this->client
            ->expects($this->once())
            ->method('post')
            ->with('https://www.strava.com/oauth/token', [
                RequestOptions::FORM_PARAMS => [
                    'grant_type' => 'authorization_code',
                    'client_id' => 'client',
                    'client_secret' => 'secret',
                    'code' => 'the-code',
                ],
            ])
            ->willReturn(new Response(200, [], Json::encode(['refresh_token' => 'the-token'])));

        $this->assertMatchesHtmlSnapshot($this->appRequestHandler->handleOauth(new Request(
            query: ['code' => 'the-code'],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ))->getContent());
    }

    public function testHandleOauthWithCodeButAnError(): void
    {
        /** @var \League\Flysystem\InMemory\InMemoryFilesystemAdapter $buildStorage */
        $buildStorage = $this->getContainer()->get('build.storage');
        $buildStorage->write('index.html', 'I am the index', []);

        $this->client
            ->expects($this->once())
            ->method('post')
            ->with('https://www.strava.com/oauth/token', [
                RequestOptions::FORM_PARAMS => [
                    'grant_type' => 'authorization_code',
                    'client_id' => 'client',
                    'client_secret' => 'secret',
                    'code' => 'the-code',
                ],
            ])
            ->willThrowException(RequestException::wrapException(
                new \GuzzleHttp\Psr7\Request('GET', 'uri'),
                new \RuntimeException('The error')
            ));

        $this->assertMatchesHtmlSnapshot($this->appRequestHandler->handleOauth(new Request(
            query: ['code' => 'the-code'],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ))->getContent());
    }

    public function testHandleOauthWithoutCode(): void
    {
        /** @var \League\Flysystem\InMemory\InMemoryFilesystemAdapter $buildStorage */
        $buildStorage = $this->getContainer()->get('build.storage');
        $buildStorage->write('index.html', 'I am the index', []);

        $this->client
            ->expects($this->never())
            ->method('post');

        $this->assertMatchesHtmlSnapshot($this->appRequestHandler->handleOauth(new Request(
            query: [],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ))->getContent());
    }

    protected function setUp(): void
    {
        $this->appRequestHandler = new AppRequestHandler(
            $this->getContainer()->get('build.storage'),
            StravaClientId::fromString('client'),
            StravaClientSecret::fromString('secret'),
            $this->client = $this->createMock(Client::class),
            $this->strava = $this->createMock(Strava::class),
            $this->getContainer()->get(Environment::class),
        );
    }
}
