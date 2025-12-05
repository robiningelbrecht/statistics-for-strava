<?php

namespace App\Tests\Application\Build\BuildPhotosHtml;

use App\Application\Build\BuildPhotosHtml\DefaultEnabledPhotoFilters;
use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\Serialization\Json;
use PHPUnit\Framework\TestCase;

class DefaultEnabledPhotoFiltersTest extends TestCase
{
    public function testFromItShouldThrow(): void
    {
        $this->expectExceptionObject(new \RuntimeException('Country code "BEE" in defaultEnabledFilters is not supported'));
        DefaultEnabledPhotoFilters::from(['countryCode' => 'BEE']);
    }

    public function testJsonSerialize(): void
    {
        $this->assertEquals(
            ['sportType' => [SportType::RIDE->value]],
            Json::encodeAndDecode(DefaultEnabledPhotoFilters::from(['sportTypes' => ['Ride']]))
        );

        $this->assertEquals(
            ['countryCode' => 'BE'],
            Json::encodeAndDecode(DefaultEnabledPhotoFilters::from(['countryCode' => 'BE']))
        );

        $this->assertEquals(
            ['sportType' => [SportType::RIDE->value], 'countryCode' => 'BE'],
            Json::encodeAndDecode(DefaultEnabledPhotoFilters::from(['sportTypes' => ['Ride'], 'countryCode' => 'BE']))
        );
    }
}
