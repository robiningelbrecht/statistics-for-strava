<?php

namespace App\Domain\Dashboard\Widget\AthleteProfile;

use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AthleteProfileChart
{
    private function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public static function create(TranslatorInterface $translator): self
    {
        return new self($translator);
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return [
            'backgroundColor' => null,
            'animation' => true,
            'grid' => [
                'left' => '5px',
                'right' => '5px',
                'bottom' => '5px',
                'containLabel' => true,
            ],
            'legend' => [
                'show' => true,
                'orient' => 'vertical',
                'right' => 0,
            ],
            'tooltip' => [
                'show' => true,
            ],
            'radar' => [
                'indicator' => [
                    ['name' => $this->translator->trans('Volume'), 'max' => 100],
                    ['name' => $this->translator->trans('Consistency'), 'max' => 100],
                    ['name' => $this->translator->trans('Intensity'), 'max' => 100],
                    ['name' => $this->translator->trans('Duration'), 'max' => 100],
                    ['name' => $this->translator->trans('Density'), 'max' => 100],
                    ['name' => $this->translator->trans('Variety'), 'max' => 100],
                ],
            ],
            'series' => [
                [
                    'type' => 'radar',
                    'lineStyle' => [
                        'width' => 1,
                        'opacity' => 1,
                    ],
                    'areaStyle' => [
                        'opacity' => 0.1,
                    ],
                    'data' => [
                        [
                            'value' => [55, 9, 56, 46, 18, 75, 98],
                            'name' => $this->translator->trans('last 30 days'),
                        ],
                        [
                            'value' => [55, 9, 56, 46, 18, 75, 98],
                            'name' => $this->translator->trans('last 90 days'),
                        ],
                    ],
                ],
            ],
        ];
    }
}
