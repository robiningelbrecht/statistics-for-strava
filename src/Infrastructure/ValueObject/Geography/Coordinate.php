<?php

namespace App\Infrastructure\ValueObject\Geography;

use App\Infrastructure\ValueObject\Number\FloatLiteral;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class Coordinate implements \JsonSerializable
{
    private function __construct(
        #[ORM\Column(type: 'float', nullable: true)]
        private Latitude $latitude,
        #[ORM\Column(type: 'float', nullable: true)]
        private Longitude $longitude)
    {
    }

    public static function createFromLatAndLng(Latitude $latitude, Longitude $longitude): Coordinate
    {
        return new self(
            $latitude,
            $longitude
        );
    }

    public static function createFromOptionalLatAndLng(?Latitude $latitude, ?Longitude $longitude): ?Coordinate
    {
        if (!$latitude instanceof Latitude) {
            return null;
        }
        if (!$longitude instanceof Longitude) {
            return null;
        }

        return new self(
            $latitude,
            $longitude
        );
    }

    public function getLatitude(): Latitude
    {
        return $this->latitude;
    }

    public function getLongitude(): Longitude
    {
        return $this->longitude;
    }

    /**
     * @return FloatLiteral[]
     */
    public function jsonSerialize(): array
    {
        return [$this->getLatitude(), $this->getLongitude()];
    }
}
