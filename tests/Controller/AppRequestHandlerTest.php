<?php

namespace App\Tests\Controller;

use App\Controller\AppRequestHandler;
use App\Tests\ContainerTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class AppRequestHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private AppRequestHandler $appRequestHandler;

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

    public function testHandleWhenNotBuilt(): void
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
            $this->getContainer()->get(Environment::class),
        );
    }
}
