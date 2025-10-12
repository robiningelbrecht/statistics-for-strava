<?php

namespace App\Tests\Domain\Zwift;

use App\Domain\Zwift\CouldNotDetermineZwiftMap;
use App\Domain\Zwift\ZwiftMap;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ZwiftMapTest extends TestCase
{
    use MatchesSnapshots;

    #[DataProvider(methodName: 'provideStartingCoordinates')]
    public function testFromStartingCoordinate(Coordinate $startingCoordinate): void
    {
        $map = ZwiftMap::forStartingCoordinate($startingCoordinate);
        $this->assertMatchesJsonSnapshot(Json::encode([
            'name' => $map->getOverlayImageUrl(),
            'bounds' => $map->getBounds(),
        ]));
    }

    public function testFromStartingCoordinateItShouldThrow(): void
    {
        $this->expectExceptionObject(new CouldNotDetermineZwiftMap('Could not determine Zwift map [1,1]'));

        ZwiftMap::forStartingCoordinate(Coordinate::createFromLatAndLng(
            Latitude::fromString('1'), Longitude::fromString('1')
        ));
    }

    public function testGetTileLayer(): void
    {
        $this->assertNull(ZwiftMap::forStartingCoordinate(Coordinate::createFromLatAndLng(
            Latitude::fromString('44.5308037'), Longitude::fromString('11.26261748')
        ))->getTileLayer());
    }

    public function testGetMinAndMaxZoom(): void
    {
        $this->assertEquals(
            18,
            ZwiftMap::forStartingCoordinate(Coordinate::createFromLatAndLng(
                Latitude::fromString('44.5308037'), Longitude::fromString('11.26261748')
            ))->getMaxZoom()
        );
        $this->assertEquals(
            12,
            ZwiftMap::forStartingCoordinate(Coordinate::createFromLatAndLng(
                Latitude::fromString('44.5308037'), Longitude::fromString('11.26261748')
            ))->getMinZoom()
        );
    }

    public function testGetOverlayImageUrl(): void
    {
        $this->assertEquals(
            '/assets/images/maps/zwift-bologna.jpg',
            ZwiftMap::forStartingCoordinate(Coordinate::createFromLatAndLng(
                Latitude::fromString('44.5308037'), Longitude::fromString('11.26261748')
            ))->getOverlayImageUrl()
        );
    }

    public static function provideStartingCoordinates(): array
    {
        return [
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('44.5308037'), Longitude::fromString('11.26261748')
                ),
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('-10.3657'), Longitude::fromString('165.7824')
                ),
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('-21.7564'), Longitude::fromString('166.26125')
                ),
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('47.2947'), Longitude::fromString('11.3501')
                ),
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('51.5362'), Longitude::fromString('-0.1776')
                ),
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('-10.7375'), Longitude::fromString('165.7828')
                ),
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('40.81725'), Longitude::fromString('-74.0227')
                ),
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('48.9058'), Longitude::fromString('2.2561')
                ),
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('37.5774'), Longitude::fromString('-77.48954')
                ),
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('55.675959999999996'), Longitude::fromString('-5.28053')
                ),
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('-11.626'), Longitude::fromString('166.87747')
                ),
            ],
            [
                Coordinate::createFromLatAndLng(
                    Latitude::fromString('54.0254'), Longitude::fromString('-1.6320')
                ),
            ],
        ];
    }
}
