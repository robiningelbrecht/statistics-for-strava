<?php

namespace App\Tests\Application\Build\BuildBestEffortsHtml;

use App\Application\Build\BuildBestEffortsHtml\BuildBestEffortsHtml;
use App\Tests\Application\BuildAppFilesTestCase;

class BuildBestEffortsHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildBestEffortsHtml());
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
