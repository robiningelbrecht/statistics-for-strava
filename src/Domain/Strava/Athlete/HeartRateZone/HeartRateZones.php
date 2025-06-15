<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\HeartRateZone;

final readonly class HeartRateZones
{
    private function __construct(
        private HeartRateZoneMode $mode,
        private HeartRateZone $zoneOne,
        private HeartRateZone $zoneTwo,
        private HeartRateZone $zoneThree,
        private HeartRateZone $zoneFour,
        private HeartRateZone $zoneFive,
    )
    {
    }

    public function getMode(): HeartRateZoneMode
    {
        return $this->mode;
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

    /**
     * @param array<string, array{from: int, to:int|null}> $zones
     */
    public static function fromScalarValues(
        HeartRateZoneMode $mode,
        array $zones,
    ): self
    {
        return new self(
            mode: $mode,
            zoneOne: new HeartRateZone(
                name: HeartRateZone::ONE,
                from: $zones['zone1']['from'],
                to: $zones['zone1']['to'],
            ),
            zoneTwo: new HeartRateZone(
                name: HeartRateZone::TWO,
                from: $zones['zone2']['from'],
                to: $zones['zone2']['to'],
            ),
            zoneThree: new HeartRateZone(
                name: HeartRateZone::THREE,
                from: $zones['zone3']['from'],
                to: $zones['zone3']['to'],
            ),
            zoneFour: new HeartRateZone(
                name: HeartRateZone::FOUR,
                from: $zones['zone4']['from'],
                to: $zones['zone4']['to'],
            ),
            zoneFive: new HeartRateZone(
                name: HeartRateZone::FIVE,
                from: $zones['zone5']['from'],
                to: $zones['zone5']['to'],
            )
        );
    }
}
