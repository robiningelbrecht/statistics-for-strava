<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;

enum LeafletMap: string
{
    case REAL_WORLD = 'real-world';
    case ZWIFT_BOLOGNA = 'zwift-bologna';
    case ZWIFT_CRIT_CITY = 'zwift-crit-city';
    case ZWIFT_FRANCE = 'zwift-france';
    case ZWIFT_INNSBRUCK = 'zwift-innsbruck';
    case ZWIFT_LONDON = 'zwift-london';
    case ZWIFT_MAKURI_ISLANDS = 'zwift-makuri-islands';
    case ZWIFT_NEW_YORK = 'zwift-new-york';
    case ZWIFT_PARIS = 'zwift-paris';
    case ZWIFT_RICHMOND = 'zwift-richmond';
    case ZWIFT_SCOTLAND = 'zwift-scotland';
    case ZWIFT_WATOPIA = 'zwift-watopia';
    case ZWIFT_YORKSHIRE = 'zwift-yorkshire';

    public function getTileLayer(): ?string
    {
        if (self::REAL_WORLD === $this) {
            return 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
        }

        return null;
    }

    public function getOverlayImageUrl(): ?string
    {
        if (self::REAL_WORLD === $this) {
            return null;
        }

        return '/assets/images/maps/'.$this->value.'.jpg';
    }

    /**
     * @return Coordinate[]
     */
    public function getBounds(): array
    {
        if (self::REAL_WORLD === $this) {
            return [];
        }

        // https://zwiftinsider.com/hilly-kom-bypass/
        // https://cf.veloviewer.com/js/vv.lmap.61.js
        return match ($this) {
            self::ZWIFT_BOLOGNA => [
                Coordinate::createFromLatAndLng(Latitude::fromString('44.5308037'), Longitude::fromString('11.26261748')),
                Coordinate::createFromLatAndLng(Latitude::fromString('44.45463821'), Longitude::fromString('11.36991729102076')),
            ],
            self::ZWIFT_CRIT_CITY => [
                Coordinate::createFromLatAndLng(Latitude::fromString('-10.3657'), Longitude::fromString('165.7824')),
                Coordinate::createFromLatAndLng(Latitude::fromString('-10.4038'), Longitude::fromString('165.8207')),
            ],
            self::ZWIFT_FRANCE => [
                Coordinate::createFromLatAndLng(Latitude::fromString('-21.64155'), Longitude::fromString('166.1384')),
                Coordinate::createFromLatAndLng(Latitude::fromString('-21.7564'), Longitude::fromString('166.26125')),
            ],
            self::ZWIFT_INNSBRUCK => [
                Coordinate::createFromLatAndLng(Latitude::fromString('47.2947'), Longitude::fromString('11.3501')),
                Coordinate::createFromLatAndLng(Latitude::fromString('47.2055'), Longitude::fromString('11.4822')),
            ],
            self::ZWIFT_LONDON => [
                Coordinate::createFromLatAndLng(Latitude::fromString('51.5362'), Longitude::fromString('-0.1776')),
                Coordinate::createFromLatAndLng(Latitude::fromString('51.4601'), Longitude::fromString('-0.0555')),
            ],
            self::ZWIFT_MAKURI_ISLANDS => [
                Coordinate::createFromLatAndLng(Latitude::fromString('-10.7375'), Longitude::fromString('165.7828')),
                Coordinate::createFromLatAndLng(Latitude::fromString('-10.831'), Longitude::fromString('165.8772')),
            ],
            self::ZWIFT_NEW_YORK => [
                Coordinate::createFromLatAndLng(Latitude::fromString('40.81725'), Longitude::fromString('-74.0227')),
                Coordinate::createFromLatAndLng(Latitude::fromString('40.74085'), Longitude::fromString('-73.9222')),
            ],
            self::ZWIFT_PARIS => [
                Coordinate::createFromLatAndLng(Latitude::fromString('48.9058'), Longitude::fromString('2.2561')),
                Coordinate::createFromLatAndLng(Latitude::fromString('48.82945'), Longitude::fromString('2.3722')),
            ],
            self::ZWIFT_RICHMOND => [
                Coordinate::createFromLatAndLng(Latitude::fromString('37.5774'), Longitude::fromString('-77.48954')),
                Coordinate::createFromLatAndLng(Latitude::fromString('37.5014'), Longitude::fromString('-77.394')),
            ],
            self::ZWIFT_SCOTLAND => [
                Coordinate::createFromLatAndLng(Latitude::fromString('55.675959999999996'), Longitude::fromString('-5.28053')),
                Coordinate::createFromLatAndLng(Latitude::fromString('55.6185'), Longitude::fromString('-5.17753')),
            ],
            self::ZWIFT_WATOPIA => [
                Coordinate::createFromLatAndLng(Latitude::fromString('-11.626'), Longitude::fromString('166.87747')),
                Coordinate::createFromLatAndLng(Latitude::fromString('-11.729'), Longitude::fromString('167.03255')),
            ],
            self::ZWIFT_YORKSHIRE => [
                Coordinate::createFromLatAndLng(Latitude::fromString('54.0254'), Longitude::fromString('-1.6320')),
                Coordinate::createFromLatAndLng(Latitude::fromString('53.9491'), Longitude::fromString('-1.5022')),
            ],
        };
    }

    public function getMaxZoom(): int
    {
        if (self::REAL_WORLD === $this) {
            return 17;
        }

        return 18;
    }

    public function getMinZoom(): int
    {
        if (self::REAL_WORLD === $this) {
            return 1;
        }

        return 12;
    }

    public function getBackgroundColor(): string
    {
        return '#bbbbb7';
    }

    public static function forZwiftStartingCoordinate(Coordinate $coordinate): self
    {
        foreach (self::cases() as $map) {
            if (LeafletMap::REAL_WORLD === $map) {
                continue;
            }
            $bounds = $map->getBounds();

            if ($coordinate->getLatitude()->toFloat() <= $bounds[0]->getLatitude()->toFloat()
                && $coordinate->getLatitude()->toFloat() >= $bounds[1]->getLatitude()->toFloat()
                && $coordinate->getLongitude()->toFloat() >= $bounds[0]->getLongitude()->toFloat()
                && $coordinate->getLongitude()->toFloat() <= $bounds[1]->getLongitude()->toFloat()) {
                return $map;
            }
        }

        throw new CouldNotDetermineLeafletMap('No map found for starting coordinate '.Json::encode($coordinate));
    }
}
