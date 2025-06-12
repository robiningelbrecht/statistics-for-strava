<?php

namespace App\Tests\Domain\Strava\Activity\Eddington\Config;

use App\Domain\Strava\Activity\Eddington\Config\EddingtonConfigItem;
use App\Domain\Strava\Activity\SportType\SportTypes;
use PHPUnit\Framework\TestCase;

class EddingtonConfigItemTest extends TestCase
{
    public function testGetId(): void
    {
        $configItem = EddingtonConfigItem::create(
            label: 'Test With 999% weird ° $ chars YO',
            showInNavBar: true,
            sportTypesToInclude: SportTypes::empty()
        );

        $this->assertEquals(
            'test-with-999-weird-chars-yo',
            $configItem->getId(),
        );
    }
}
