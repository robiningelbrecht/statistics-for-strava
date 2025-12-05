<?php

namespace App\Tests\Application\Build\BuildPhotosHtml;

use App\Application\Build\BuildPhotosHtml\BuildPhotosHtml;
use App\Tests\Application\BuildAppFilesTestCase;

class BuildPhotosHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildPhotosHtml());
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
