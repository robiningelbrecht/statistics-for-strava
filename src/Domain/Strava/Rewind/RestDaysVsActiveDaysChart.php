<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind;

use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class RestDaysVsActiveDaysChart
{
    private function __construct(
        private int $numberOfActiveDays,
        private int $numberOfRestDays,
        private TranslatorInterface $translator,
    ) {
    }

    public static function create(
        int $numberOfActiveDays,
        int $numberOfRestDays,
        TranslatorInterface $translator,
    ): self {
        return new self(
            numberOfActiveDays: $numberOfActiveDays,
            numberOfRestDays: $numberOfRestDays,
            translator: $translator,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return [
            'backgroundColor' => null,
            'animation' => false,
            'grid' => [
                'left' => '0%',
                'right' => '0%',
                'bottom' => '0%',
                'containLabel' => true,
            ],
            'center' => ['50%', '50%'],
            'legend' => [
                'show' => false,
            ],
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{b}: {c}',
            ],
            'series' => [
                [
                    'type' => 'pie',
                    'itemStyle' => [
                        'borderColor' => '#fff',
                        'borderWidth' => 2,
                    ],
                    'label' => [
                        'formatter' => "{title|{b}}\n{sub|{c}}",
                        'lineHeight' => 15,
                        'rich' => [
                            'title' => [
                                'fontWeight' => 'bold',
                            ],
                            'sub' => [
                                'fontSize' => 12,
                            ],
                        ],
                    ],
                    'data' => [
                        [
                            'value' => $this->numberOfActiveDays,
                            'name' => $this->translator->trans('Active days'),
                        ],
                        [
                            'value' => $this->numberOfRestDays,
                            'name' => $this->translator->trans('Rest days'),
                        ],
                    ],
                ],
            ],
        ];
    }
}
