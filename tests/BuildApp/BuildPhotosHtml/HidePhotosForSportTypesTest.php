<?php

namespace App\Tests\BuildApp\BuildPhotosHtml;

use App\BuildApp\BuildPhotosHtml\HidePhotosForSportTypes;
use App\Domain\Activity\SportType\SportType;
use PHPUnit\Framework\TestCase;

class HidePhotosForSportTypesTest extends TestCase
{
    public function testFrom(): void
    {
        $this->assertEquals(
            HidePhotosForSportTypes::fromArray([SportType::RIDE]),
            HidePhotosForSportTypes::from(['Ride'])
        );
    }

    public function testFromEmpty(): void
    {
        $this->assertEquals(
            HidePhotosForSportTypes::empty(),
            HidePhotosForSportTypes::from([])
        );
    }
}
