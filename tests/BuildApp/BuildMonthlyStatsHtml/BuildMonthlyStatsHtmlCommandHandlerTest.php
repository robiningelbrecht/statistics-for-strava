<?php

namespace App\Tests\BuildApp\BuildMonthlyStatsHtml;

use App\BuildApp\BuildMonthlyStatsHtml\BuildMonthlyStatsHtml;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\BuildApp\BuildAppFilesTestCase;

class BuildMonthlyStatsHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildMonthlyStatsHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
