<?php

declare(strict_types=1);

namespace App\Domain\Rewind;

use App\Domain\Activity\SportType\SportType;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class MovingTimePerSportTypeChart
{
    private function __construct(
        /** @var array<string, int> */
        private array $movingTimePerSportType,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<string, int> $movingTimePerSportType
     */
    public static function create(
        array $movingTimePerSportType,
        TranslatorInterface $translator,
    ): self {
        return new self(
            movingTimePerSportType: $movingTimePerSportType,
            translator: $translator,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $data = [];
        foreach ($this->movingTimePerSportType as $sportType => $time) {
            $data[] = [
                'value' => round($time / 3600),
                'name' => SportType::from($sportType)->trans($this->translator),
            ];
        }

        return [
            'backgroundColor' => null,
            'animation' => true,
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
                'formatter' => '{b}: {c}h',
            ],
            'series' => [
                [
                    'type' => 'pie',
                    'itemStyle' => [
                        'borderColor' => '#fff',
                        'borderWidth' => 2,
                    ],
                    'label' => [
                        'formatter' => "{sportType|{b}}\n{sub|{c}h}",
                        'lineHeight' => 15,
                        'rich' => [
                            'sportType' => [
                                'fontWeight' => 'bold',
                            ],
                            'sub' => [
                                'fontSize' => 12,
                            ],
                        ],
                    ],
                    'data' => $data,
                ],
            ],
        ];
    }
}
