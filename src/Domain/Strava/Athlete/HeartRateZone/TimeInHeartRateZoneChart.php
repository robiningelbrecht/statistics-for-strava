<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\HeartRateZone;

use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class TimeInHeartRateZoneChart
{
    private function __construct(
        private int $timeInSecondsInHeartRateZoneOne,
        private int $timeInSecondsInHeartRateZoneTwo,
        private int $timeInSecondsInHeartRateZoneThree,
        private int $timeInSecondsInHeartRateZoneFour,
        private int $timeInSecondsInHeartRateZoneFive,
        private TranslatorInterface $translator,
    ) {
    }

    public static function create(
        int $timeInSecondsInHeartRateZoneOne,
        int $timeInSecondsInHeartRateZoneTwo,
        int $timeInSecondsInHeartRateZoneThree,
        int $timeInSecondsInHeartRateZoneFour,
        int $timeInSecondsInHeartRateZoneFive,
        TranslatorInterface $translator,
    ): self {
        return new self(
            timeInSecondsInHeartRateZoneOne: $timeInSecondsInHeartRateZoneOne,
            timeInSecondsInHeartRateZoneTwo: $timeInSecondsInHeartRateZoneTwo,
            timeInSecondsInHeartRateZoneThree: $timeInSecondsInHeartRateZoneThree,
            timeInSecondsInHeartRateZoneFour: $timeInSecondsInHeartRateZoneFour,
            timeInSecondsInHeartRateZoneFive: $timeInSecondsInHeartRateZoneFive,
            translator: $translator
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
                            'value' => $this->timeInSecondsInHeartRateZoneOne,
                            'name' => $this->translator->trans('Zone 1 (recovery)'),
                            'itemStyle' => [
                                'color' => '#DF584A',
                            ],
                        ],
                        [
                            'value' => $this->timeInSecondsInHeartRateZoneTwo,
                            'name' => $this->translator->trans('Zone 2 (aerobic)'),
                            'itemStyle' => [
                                'color' => '#D63522',
                            ],
                        ],
                        [
                            'value' => $this->timeInSecondsInHeartRateZoneThree,
                            'name' => $this->translator->trans('Zone 3 (aerobic/anaerobic)'),
                            'itemStyle' => [
                                'color' => '#BD2D22',
                            ],
                        ],
                        [
                            'value' => $this->timeInSecondsInHeartRateZoneFour,
                            'name' => $this->translator->trans('Zone 4 (anaerobic)'),
                            'itemStyle' => [
                                'color' => '#942319',
                            ],
                        ],
                        [
                            'value' => $this->timeInSecondsInHeartRateZoneFive,
                            'name' => $this->translator->trans('Zone 5 (maximal)'),
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
