<?php

namespace App\Tests\Application\Build\BuildMilestonesHtml;

use App\Application\Build\BuildMilestonesHtml\BuildMilestonesHtml;
use App\Tests\Application\BuildAppFilesTestCase;

class BuildMilestonesHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildMilestonesHtml());
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
