<?php

namespace App\Tests\Application\Import\ImportActivities;

use App\Application\Import\ImportActivities\ActivitiesToSkipDuringImport;
use App\Domain\Activity\ActivityId;
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
