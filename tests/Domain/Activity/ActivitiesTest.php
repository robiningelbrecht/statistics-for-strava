<?php

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\Activities;
use PHPUnit\Framework\TestCase;

class ActivitiesTest extends TestCase
{
    public function testGetFirstActivityStartDateItShouldThrowWhenNotFound(): void
    {
        $this->expectExceptionObject(new \RuntimeException('No activities found'));
        Activities::empty()->getFirstActivityStartDate();
    }
}
