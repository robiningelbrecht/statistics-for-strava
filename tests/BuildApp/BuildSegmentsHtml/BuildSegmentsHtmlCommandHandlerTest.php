<?php

namespace App\Tests\BuildApp\BuildSegmentsHtml;

use App\BuildApp\BuildSegmentsHtml\BuildSegmentsHtml;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\BuildApp\BuildAppFilesTestCase;

class BuildSegmentsHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildSegmentsHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
