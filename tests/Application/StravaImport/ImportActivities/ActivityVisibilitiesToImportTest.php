<?php

namespace App\Tests\Application\StravaImport\ImportActivities;

use App\Application\StravaImport\ImportActivities\ActivityVisibilitiesToImport;
use App\Domain\Activity\ActivityVisibility;
use PHPUnit\Framework\TestCase;

class ActivityVisibilitiesToImportTest extends TestCase
{
    public function testFrom(): void
    {
        $this->assertEquals(
            ActivityVisibilitiesToImport::fromArray(ActivityVisibility::cases()),
            ActivityVisibilitiesToImport::from([])
        );

        $this->assertEquals(
            ActivityVisibilitiesToImport::fromArray([ActivityVisibility::ONLY_ME, ActivityVisibility::EVERYONE]),
            ActivityVisibilitiesToImport::from(['only_me', 'everyone'])
        );
    }
}
