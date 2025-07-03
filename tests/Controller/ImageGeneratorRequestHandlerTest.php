<?php

namespace App\Tests\Controller;

use App\Controller\ImageGeneratorRequestHandler;
use App\Domain\App\BuildIndexHtml\IndexHtml;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\ProvideTestData;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ImageGeneratorRequestHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;
    use ProvideTestData;

    private ImageGeneratorRequestHandler $imageGeneratorRequestHandler;

    public function testHandle(): void
    {
        $this->provideFullTestSet();

        /** @var \League\Flysystem\InMemory\InMemoryFilesystemAdapter $buildStorage */
        $buildStorage = $this->getContainer()->get('build.storage');
        $buildStorage->write('index.html', 'I am the index', []);

        $this->assertMatchesHtmlSnapshot($this->imageGeneratorRequestHandler->handle(new Request(
            query: [],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ))->getContent());
    }

    public function testHandleWhenNoBuild(): void
    {
        $response = $this->imageGeneratorRequestHandler->handle(new Request(
            query: [],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ));

        $this->assertEquals(
            new RedirectResponse('/', Response::HTTP_FOUND),
            $response,
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->imageGeneratorRequestHandler = new ImageGeneratorRequestHandler(
            indexHtml: $this->getContainer()->get(IndexHtml::class),
            buildStorage: $this->getContainer()->get('build.storage'),
            twig: $this->getContainer()->get(Environment::class),
            clock: PausedClock::on(SerializableDateTime::fromString('2025-01-01'))
        );
    }
}
