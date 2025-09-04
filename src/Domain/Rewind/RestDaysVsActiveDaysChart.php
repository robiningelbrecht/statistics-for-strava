<?php

declare(strict_types=1);

namespace App\Domain\Rewind;

use function Symfony\Component\Translation\t;

final readonly class RestDaysVsActiveDaysChart
{
    private function __construct(
        private int $numberOfActiveDays,
        private int $numberOfRestDays,
    ) {
    }

    public static function create(
        int $numberOfActiveDays,
        int $numberOfRestDays,
    ): self {
        return new self(
            numberOfActiveDays: $numberOfActiveDays,
            numberOfRestDays: $numberOfRestDays,
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
                            'name' => (string) t('Active days'),
                        ],
                        [
                            'value' => $this->numberOfRestDays,
                            'name' => (string) t('Rest days'),
                        ],
                    ],
                ],
            ],
        ];
    }
}
