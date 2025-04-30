<?php

namespace App\Tests\Controller;

use App\Controller\AppRequestHandler;
use App\Domain\Strava\Strava;
use App\Tests\ContainerTestCase;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class AppRequestHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private AppRequestHandler $appRequestHandler;
    private MockObject $strava;

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
            ->method('getAccessToken')
            ->willThrowException(RequestException::wrapException(
                new \GuzzleHttp\Psr7\Request('GET', 'uri'),
                new \RuntimeException()
            ));

        $response = $this->appRequestHandler->handle(new Request(
            query: [],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ));

        $this->assertEquals(
            new RedirectResponse('/strava-oauth', \Symfony\Component\HttpFoundation\Response::HTTP_FOUND),
            $response,
        );
    }

    public function testHandleWhenValidRefreshTokenButNoBuild(): void
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

    protected function setUp(): void
    {
        $this->appRequestHandler = new AppRequestHandler(
            $this->getContainer()->get('build.storage'),
            $this->strava = $this->createMock(Strava::class),
            $this->getContainer()->get(Environment::class),
        );
    }
}
