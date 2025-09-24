<?php

namespace App\Tests\BuildApp\ConfigureAppColors;

use App\BuildApp\ConfigureAppColors\ConfigureAppColors;
use App\Tests\BuildApp\BuildAppFilesTestCase;

class ConfigureAppColorsCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new ConfigureAppColors());
        $this->assertFileSystemWrites($this->getContainer()->get('default.storage'));
    }
}
