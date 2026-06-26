<?php

namespace App\Tests\Controller;

use App\Controller\AppRequestHandler;
use App\Domain\Import\ImportMode;
use App\Domain\Strava\InvalidStravaAccessToken;
use App\Domain\Strava\Strava;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class AppRequestHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private AppRequestHandler $appRequestHandler;
    private MockObject $strava;

    public function testHandle(): void
    {
        /** @var \League\Flysystem\InMemory\InMemoryFilesystemAdapter $buildStorage */
        $buildStorage = $this->getContainer()->get('build_html.storage');
        $buildStorage->write('index.html', 'I am the index', []);

        $this->strava
            ->expects($this->never())
            ->method('verifyAccessToken');

        $this->assertMatchesHtmlSnapshot($this->appRequestHandler->handle()->getContent());
    }

    public function testHandleWhenInvalidRefreshTokenUsingStravaApiImportMode(): void
    {
        $this->strava
            ->expects($this->once())
            ->method('verifyAccessToken')
            ->willThrowException(new InvalidStravaAccessToken());

        $response = $this->appRequestHandler->handle();

        $this->assertEquals(
            new RedirectResponse('/strava-oauth', Response::HTTP_FOUND),
            $response,
        );
    }

    public function testHandleWhenValidRefreshTokenButNoBuildUsingStravaApiImportMode(): void
    {
        $this->strava
            ->expects($this->once())
            ->method('verifyAccessToken');

        $this->assertMatchesHtmlSnapshot($this->appRequestHandler->handle()->getContent());
    }

    public function testHandleWhenNoBuildUsingFileImportMode(): void
    {
        $this->strava
            ->expects($this->never())
            ->method('verifyAccessToken');

        $appRequestHandler = new AppRequestHandler(
            $this->getContainer()->get('build_html.storage'),
            $this->strava,
            $this->getContainer()->get(Environment::class),
            ImportMode::FILES,
        );

        $this->assertMatchesHtmlSnapshot($appRequestHandler->handle()->getContent());
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->appRequestHandler = new AppRequestHandler(
            $this->getContainer()->get('build_html.storage'),
            $this->strava = $this->createMock(Strava::class),
            $this->getContainer()->get(Environment::class),
            ImportMode::STRAVA_API,
        );
    }
}
