<?php

namespace App\Tests\BuildApp\BuildChallengesHtml;

use App\BuildApp\BuildChallengesHtml\BuildChallengesHtml;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\BuildApp\BuildAppFilesTestCase;

class BuildChallengesHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildChallengesHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
