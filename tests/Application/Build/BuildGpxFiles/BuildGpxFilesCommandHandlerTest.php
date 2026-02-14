<?php

namespace App\Tests\Application\Build\BuildGpxFiles;

use App\Application\Build\BuildGpxFiles\BuildGpxFiles;
use App\Tests\Application\BuildAppFilesTestCase;

class BuildGpxFilesCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        /** @var \League\Flysystem\FilesystemOperator $fileStorage */
        $fileStorage = $this->getContainer()->get('file.storage');
        $fileStorage->write('activities/gpx/el-gpx.gpx', 'content');

        $this->commandBus->dispatch(new BuildGpxFiles());
        $this->commandBus->dispatch(new BuildGpxFiles());

        $this->assertFileSystemWrites(
            fileSystem: $this->getContainer()->get('api.storage'),
            contentIsCompressed: true
        );

        $this->assertFalse(
            $fileStorage->directoryExists('activities/gpx'),
        );
    }
}
