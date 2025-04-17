<?php

namespace App\Tests\Domain\App\BuildRewindHtml;

use App\Domain\App\BuildRewindHtml\BuildRewindHtml;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildRewindHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->commandBus->dispatch(new BuildRewindHtml(SerializableDateTime::fromString('2025-10-01T00:00:00+00:00')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
