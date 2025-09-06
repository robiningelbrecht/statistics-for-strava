<?php

declare(strict_types=1);

namespace App\Domain\Athlete\HeartRateZone;

use function Symfony\Component\Translation\t;

final readonly class TimeInHeartRateZoneChart
{
    private function __construct(
        private TimeInHeartRateZones $timeInHeartRateZones,
    ) {
    }

    public static function create(
        TimeInHeartRateZones $timeInHeartRateZones,
    ): self {
        return new self(
            timeInHeartRateZones: $timeInHeartRateZones,
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        return [
            'backgroundColor' => null,
            'animation' => true,
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{d}%',
            ],
            'series' => [
                [
                    'type' => 'pie',
                    'itemStyle' => [
                        'borderColor' => '#fff',
                        'borderWidth' => 2,
                    ],
                    'label' => [
                        'formatter' => "{zone|{b}}\n{sub|{d}%}",
                        'lineHeight' => 15,
                        'rich' => [
                            'zone' => [
                                'fontWeight' => 'bold',
                            ],
                            'sub' => [
                                'fontSize' => 12,
                            ],
                        ],
                    ],
                    'data' => [
                        [
                            'value' => $this->timeInHeartRateZones->getTimeInZoneOne(),
                            'name' => (string) t('Zone 1 (recovery)'),
                            'itemStyle' => [
                                'color' => '#DF584A',
                            ],
                        ],
                        [
                            'value' => $this->timeInHeartRateZones->getTimeInZoneTwo(),
                            'name' => (string) t('Zone 2 (aerobic)'),
                            'itemStyle' => [
                                'color' => '#D63522',
                            ],
                        ],
                        [
                            'value' => $this->timeInHeartRateZones->getTimeInZoneThree(),
                            'name' => (string) t('Zone 3 (aerobic/anaerobic)'),
                            'itemStyle' => [
                                'color' => '#BD2D22',
                            ],
                        ],
                        [
                            'value' => $this->timeInHeartRateZones->getTimeInZoneFour(),
                            'name' => (string) t('Zone 4 (anaerobic)'),
                            'itemStyle' => [
                                'color' => '#942319',
                            ],
                        ],
                        [
                            'value' => $this->timeInHeartRateZones->getTimeInZoneFive(),
                            'name' => (string) t('Zone 5 (maximal)'),
                            'itemStyle' => [
                                'color' => '#6A1009',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
