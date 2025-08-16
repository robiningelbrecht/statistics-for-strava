<?php

namespace App\Tests\BuildApp\BuildGpxFiles;

use App\BuildApp\BuildGpxFiles\BuildGpxFiles;
use App\Tests\BuildApp\BuildAppFilesTestCase;

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
