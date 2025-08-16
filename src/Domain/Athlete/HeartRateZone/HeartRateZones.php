<?php

declare(strict_types=1);

namespace App\Domain\Athlete\HeartRateZone;

final readonly class HeartRateZones
{
    private function __construct(
        private HeartRateZone $zoneOne,
        private HeartRateZone $zoneTwo,
        private HeartRateZone $zoneThree,
        private HeartRateZone $zoneFour,
        private HeartRateZone $zoneFive,
    ) {
    }

    /**
     * @return HeartRateZone[]
     */
    public function getZones(): array
    {
        return [
            $this->zoneOne,
            $this->zoneTwo,
            $this->zoneThree,
            $this->zoneFour,
            $this->zoneFive,
        ];
    }

    public function getZoneOne(): HeartRateZone
    {
        return $this->zoneOne;
    }

    public function getZoneTwo(): HeartRateZone
    {
        return $this->zoneTwo;
    }

    public function getZoneThree(): HeartRateZone
    {
        return $this->zoneThree;
    }

    public function getZoneFour(): HeartRateZone
    {
        return $this->zoneFour;
    }

    public function getZoneFive(): HeartRateZone
    {
        return $this->zoneFive;
    }

    /**
     * @param array<string, array{from: int, to:int|null}> $zones
     */
    public static function fromScalarValues(
        HeartRateZoneMode $mode,
        array $zones,
    ): self {
        return new self(
            zoneOne: new HeartRateZone(
                name: HeartRateZone::ONE,
                mode: $mode,
                from: $zones['zone1']['from'],
                to: $zones['zone1']['to'],
            ),
            zoneTwo: new HeartRateZone(
                name: HeartRateZone::TWO,
                mode: $mode,
                from: $zones['zone2']['from'],
                to: $zones['zone2']['to'],
            ),
            zoneThree: new HeartRateZone(
                name: HeartRateZone::THREE,
                mode: $mode,
                from: $zones['zone3']['from'],
                to: $zones['zone3']['to'],
            ),
            zoneFour: new HeartRateZone(
                name: HeartRateZone::FOUR,
                mode: $mode,
                from: $zones['zone4']['from'],
                to: $zones['zone4']['to'],
            ),
            zoneFive: new HeartRateZone(
                name: HeartRateZone::FIVE,
                mode: $mode,
                from: $zones['zone5']['from'],
                to: $zones['zone5']['to'],
            )
        );
    }
}
