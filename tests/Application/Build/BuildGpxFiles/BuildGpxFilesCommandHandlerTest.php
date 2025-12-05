<?php

namespace App\Tests\Application\Build\BuildGpxFiles;

use App\Application\Build\BuildGpxFiles\BuildGpxFiles;
use App\Tests\Application\BuildAppFilesTestCase;

class BuildGpxFilesCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildGpxFiles());
        $this->commandBus->dispatch(new BuildGpxFiles());

        $this->assertFileSystemWrites($this->getContainer()->get('file.storage'));
    }
}
