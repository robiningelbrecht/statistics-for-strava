<?php

namespace App\Tests\Application\Build\BuildActivitiesHtml;

use App\Application\Build\BuildActivitiesHtml\BuildActivitiesHtml;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Application\BuildAppFilesTestCase;

class BuildActivitiesHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildActivitiesHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
        $this->assertFileSystemWrites(
            fileSystem: $this->getContainer()->get('api.storage'),
            contentIsCompressed: true
        );
    }
}
