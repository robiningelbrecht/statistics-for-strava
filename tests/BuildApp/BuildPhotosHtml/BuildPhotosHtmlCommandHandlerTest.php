<?php

namespace App\Tests\BuildApp\BuildPhotosHtml;

use App\BuildApp\BuildPhotosHtml\BuildPhotosHtml;
use App\Tests\BuildApp\BuildAppFilesTestCase;

class BuildPhotosHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildPhotosHtml());
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
