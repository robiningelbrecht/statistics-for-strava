<?php

namespace App\Tests\Controller;

use App\Controller\ApiRequestHandler;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Spatie\Snapshots\MatchesSnapshots;

class ApiRequestHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private ApiRequestHandler $apiRequestHandler;

    public function testHandle(): void
    {
        /** @var \League\Flysystem\InMemory\InMemoryFilesystemAdapter $buildStorage */
        $buildStorage = $this->getContainer()->get('api.storage');
        $buildStorage->write('el-file.json', Json::encodeAndCompress(['lol']), []);

        $this->assertEquals(
            '["lol"]',
            $this->apiRequestHandler->handle('el-file.json')->getContent()
        );
    }

    public function testHandleWhenFileNotFound(): void
    {
        $this->assertEquals(
            404,
            $this->apiRequestHandler->handle('el-file.json')->getStatusCode()
        );
    }

    public function testHandleWhenFileCouldNotBeRead(): void
    {
        $this->apiRequestHandler = new ApiRequestHandler(
            $fileSystem = $this->createMock(FilesystemOperator::class),
        );

        $fileSystem
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $fileSystem
            ->expects($this->once())
            ->method('read')
            ->willThrowException(new UnableToReadFile());

        $this->assertEquals(
            404,
            $this->apiRequestHandler->handle('el-file.json')->getStatusCode()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->apiRequestHandler = new ApiRequestHandler(
            $this->getContainer()->get('api.storage'),
        );
    }
}
