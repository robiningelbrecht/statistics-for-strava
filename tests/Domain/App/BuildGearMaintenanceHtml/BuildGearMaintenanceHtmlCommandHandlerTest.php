<?php

namespace App\Tests\Domain\App\BuildGearMaintenanceHtml;

use App\Domain\App\BuildGearMaintenanceHtml\BuildGearMaintenanceHtml;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildGearMaintenanceHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildGearMaintenanceHtml());
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
