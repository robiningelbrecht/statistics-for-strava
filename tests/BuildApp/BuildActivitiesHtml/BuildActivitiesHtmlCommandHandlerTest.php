<?php

namespace App\Tests\BuildApp\BuildActivitiesHtml;

use App\BuildApp\BuildActivitiesHtml\BuildActivitiesHtml;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\BuildApp\BuildAppFilesTestCase;

class BuildActivitiesHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildActivitiesHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
