<?php

namespace App\Tests\Application\Build\BuildDashboardHtml;

use App\Application\Build\BuildDashboardHtml\BuildDashboardHtml;
use App\Tests\Application\BuildAppFilesTestCase;

class BuildDashboardHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildDashboardHtml());
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
