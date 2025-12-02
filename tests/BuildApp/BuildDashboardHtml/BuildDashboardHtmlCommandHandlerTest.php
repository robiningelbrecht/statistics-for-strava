<?php

namespace App\Tests\BuildApp\BuildDashboardHtml;

use App\BuildApp\BuildDashboardHtml\BuildDashboardHtml;
use App\Tests\BuildApp\BuildAppFilesTestCase;

class BuildDashboardHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildDashboardHtml());
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
