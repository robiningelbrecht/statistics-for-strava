<?php

namespace App\Tests\Application\Build\BuildRecordingDevices;

use App\Application\Build\BuildRecordingDevices\BuildRecordingDevices;
use App\Tests\Application\BuildAppFilesTestCase;

class BuildRecordingDevicesCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildRecordingDevices());
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
