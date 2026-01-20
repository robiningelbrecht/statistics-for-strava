<?php

namespace App\Tests\Application\Import\ImportActivities;

use App\Application\Import\ImportActivities\NumberOfNewActivitiesToProcessPerImport;
use PHPUnit\Framework\TestCase;

class NumberOfNewActivitiesToProcessPerImportTest extends TestCase
{
    public function testMaxNumberOfActivitiesProcessed(): void
    {
        $numberOfActivitiesToProcessPerImport = NumberOfNewActivitiesToProcessPerImport::fromInt(2);

        $this->assertFalse(
            $numberOfActivitiesToProcessPerImport->maxNumberProcessed()
        );
        $numberOfActivitiesToProcessPerImport->increaseNumberOfProcessedActivities();
        $this->assertFalse(
            $numberOfActivitiesToProcessPerImport->maxNumberProcessed()
        );
        $numberOfActivitiesToProcessPerImport->increaseNumberOfProcessedActivities();
        $this->assertTrue(
            $numberOfActivitiesToProcessPerImport->maxNumberProcessed()
        );
    }

    public function testItShouldThrow(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('NumberOfNewActivitiesToProcessPerImport must be greater than 0'));

        NumberOfNewActivitiesToProcessPerImport::fromInt(0);
    }
}
