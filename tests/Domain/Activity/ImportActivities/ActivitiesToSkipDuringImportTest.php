<?php

namespace App\Tests\Domain\Activity\ImportActivities;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ImportActivities\ActivitiesToSkipDuringImport;
use PHPUnit\Framework\TestCase;

class ActivitiesToSkipDuringImportTest extends TestCase
{
    public function testFrom(): void
    {
        $this->assertEquals(
            ActivitiesToSkipDuringImport::empty(),
            ActivitiesToSkipDuringImport::from([])
        );

        $this->assertEquals(
            ActivitiesToSkipDuringImport::empty()->add(ActivityId::fromUnprefixed('test')),
            ActivitiesToSkipDuringImport::from(['test'])
        );
    }
}
