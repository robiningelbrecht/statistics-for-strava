<?php

namespace App\Tests\Domain\Strava;

use App\Domain\Strava\CouldNotDetermineLeafletMap;
use App\Domain\Strava\LeafletMap;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class LeafletMapTest extends TestCase
{
    use MatchesSnapshots;

    public function testGetBounds(): void
    {
        $bounds = [];
        foreach (LeafletMap::cases() as $map) {
            $bounds[$map->value] = $map->getBounds();
        }
        $this->assertMatchesJsonSnapshot(Json::encode($bounds));
    }

    #[DataProvider(methodName: 'provideStartingCoordinates')]
    public function testFromStartingCoordinate(Coordinate $startingCoordinate, LeafletMap $expectedLeafletMap): void
    {
        $this->assertEquals(
            LeafletMap::forZwiftStartingCoordinate($startingCoordinate),
            $expectedLeafletMap
        );
    }

    public function testFromStartingCoordinateItShouldThrow(): void
    {
        $this->expectExceptionObject(new CouldNotDetermineLeafletMap('No map found for starting coordinate [1,1]'));

        LeafletMap::forZwiftStartingCoordinate(Coordinate::createFromLatAndLng(
            Latitude::fromString('1'), Longitude::fromString('1')
        ));
    }

    public function testGetTileLayer(): void
    {
        $this->assertEquals(
            'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            LeafletMap::REAL_WORLD->getTileLayer()
        );

        $this->assertNull(LeafletMap::ZWIFT_LONDON->getTileLayer());
    }

    public function testGetMinAndMaxZoom(): void
    {
        $this->assertEquals(
            14,
            LeafletMap::REAL_WORLD->getMaxZoom()
        );
        $this->assertEquals(
            1,
            LeafletMap::REAL_WORLD->getMinZoom()
        );
        $this->assertEquals(
            18,
            LeafletMap::ZWIFT_LONDON->getMaxZoom()
        );
        $this->assertEquals(
            12,
            LeafletMap::ZWIFT_LONDON->getMinZoom()
        );
    }

    public function testGetOverlayImageUrl(): void
    {
        $this->assertEquals(
            '/assets/images/maps/zwift-london.jpg',
            LeafletMap::ZWIFT_LONDON->getOverlayImageUrl()
        );

        $this->assertNull(LeafletMap::REAL_WORLD->getOverlayImageUrl());
    }

    public static function provideStartingCoordinates(): array
    {
        return [
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('44.5308037'), Longitude::fromString('11.26261748')
                ),
                LeafletMap::ZWIFT_BOLOGNA,
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('-10.3657'), Longitude::fromString('165.7824')
                ),
                LeafletMap::ZWIFT_CRIT_CITY,
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('-21.7564'), Longitude::fromString('166.26125')
                ),
                LeafletMap::ZWIFT_FRANCE,
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('47.2947'), Longitude::fromString('11.3501')
                ),
                LeafletMap::ZWIFT_INNSBRUCK,
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('51.5362'), Longitude::fromString('-0.1776')
                ),
                LeafletMap::ZWIFT_LONDON,
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('-10.7375'), Longitude::fromString('165.7828')
                ),
                LeafletMap::ZWIFT_MAKURI_ISLANDS,
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('40.81725'), Longitude::fromString('-74.0227')
                ),
                LeafletMap::ZWIFT_NEW_YORK,
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('48.9058'), Longitude::fromString('2.2561')
                ),
                LeafletMap::ZWIFT_PARIS,
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('37.5774'), Longitude::fromString('-77.48954')
                ),
                LeafletMap::ZWIFT_RICHMOND,
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('55.675959999999996'), Longitude::fromString('-5.28053')
                ),
                LeafletMap::ZWIFT_SCOTLAND,
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('-11.626'), Longitude::fromString('166.87747')
                ),
                LeafletMap::ZWIFT_WATOPIA,
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('54.0254'), Longitude::fromString('-1.6320')
                ),
                LeafletMap::ZWIFT_YORKSHIRE,
            ],
        ];
    }
}
