<?php

namespace App\Tests\Controller;

use App\Controller\StravaOAuthRequestHandler;
use App\Domain\App\AppUrl;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class StravaOAuthRequestHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private StravaOAuthRequestHandler $stravaOAuthRequestHandler;
    private MockObject $strava;
    private MockObject $client;

    public function testHandleWithValidRefreshToken(): void
    {
        $this->strava
            ->expects($this->once())
            ->method('getAccessToken');

        $this->assertEquals(
            new RedirectResponse('/', \Symfony\Component\HttpFoundation\Response::HTTP_FOUND),
            $this->stravaOAuthRequestHandler->handle(new Request(
                query: ['code' => 'the-code'],
                request: [],
                attributes: [],
                cookies: [],
                files: [],
                server: [],
                content: [],
            ))
        );
    }

    public function testHandleWithCode(): void
    {
        $this->strava
            ->expects($this->once())
            ->method('getAccessToken')
            ->willThrowException(RequestException::wrapException(
                new \GuzzleHttp\Psr7\Request('GET', 'uri'),
                new \RuntimeException()
            ));

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

        $this->assertMatchesHtmlSnapshot($this->stravaOAuthRequestHandler->handle(new Request(
            query: ['code' => 'the-code'],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ))->getContent());
    }

    public function testHandleWithCodeButAnError(): void
    {
        $this->strava
            ->expects($this->once())
            ->method('getAccessToken')
            ->willThrowException(RequestException::wrapException(
                new \GuzzleHttp\Psr7\Request('GET', 'uri'),
                new \RuntimeException()
            ));

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
            ->willThrowException(new RequestException(
                message: 'The error',
                request: new \GuzzleHttp\Psr7\Request('GET', 'uri'),
                response: new Response(
                    404,
                    [],
                    Json::encode(['error' => 'The error']
                    )
                )));

        $this->assertMatchesHtmlSnapshot($this->stravaOAuthRequestHandler->handle(new Request(
            query: ['code' => 'the-code'],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ))->getContent());
    }

    public function testHandleWithoutCode(): void
    {
        $this->strava
            ->expects($this->once())
            ->method('getAccessToken')
            ->willThrowException(RequestException::wrapException(
                new \GuzzleHttp\Psr7\Request('GET', 'uri'),
                new \RuntimeException()
            ));

        $this->client
            ->expects($this->never())
            ->method('post');

        $this->assertMatchesHtmlSnapshot($this->stravaOAuthRequestHandler->handle(new Request(
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
        $this->stravaOAuthRequestHandler = new StravaOAuthRequestHandler(
            StravaClientId::fromString('client'),
            StravaClientSecret::fromString('secret'),
            $this->strava = $this->createMock(Strava::class),
            AppUrl::fromString('https://www.da-bomb.com'),
            $this->client = $this->createMock(Client::class),
            $this->getContainer()->get(Environment::class),
        );
    }
}
