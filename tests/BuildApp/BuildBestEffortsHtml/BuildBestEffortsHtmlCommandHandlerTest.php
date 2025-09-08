<?php

namespace App\Tests\BuildApp\BuildBestEffortsHtml;

use App\BuildApp\BuildBestEffortsHtml\BuildBestEffortsHtml;
use App\Tests\BuildApp\BuildAppFilesTestCase;

class BuildBestEffortsHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildBestEffortsHtml());
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
