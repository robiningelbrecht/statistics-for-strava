<?php

declare(strict_types=1);

namespace App\Domain\Ftp;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class FtpHistoryChart
{
    private function __construct(
        private Ftps $ftps,
        private SerializableDateTime $now,
    ) {
    }

    public static function create(
        Ftps $ftps,
        SerializableDateTime $now,
    ): self {
        return new self(
            ftps: $ftps,
            now: $now
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        return [
            'animation' => true,
            'backgroundColor' => null,
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'grid' => [
                'top' => '2%',
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'time',
                    'boundaryGap' => false,
                    'axisTick' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => [
                            'year' => '{yyyy}',
                            'month' => '{MMM}',
                            'day' => '',
                            'hour' => '{HH}:{mm}',
                            'minute' => '{HH}:{mm}',
                            'second' => '{HH}:{mm}:{ss}',
                            'millisecond' => '{hh}:{mm}:{ss} {SSS}',
                            'none' => '{yyyy}-{MM}-{dd}',
                        ],
                    ],
                    'splitLine' => [
                        'show' => true,
                        'lineStyle' => [
                            'color' => '#E0E6F1',
                        ],
                    ],
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} w',
                    ],
                    'min' => $this->ftps->min(fn (Ftp $ftp): int => $ftp->getFtp()->getValue()) - 10,
                ],
                $this->ftps->getFirst()?->getRelativeFtp() ? [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} w/kg',
                    ],
                    'min' => $this->ftps->min(fn (Ftp $ftp): ?float => $ftp->getRelativeFtp()) - 1,
                ] : [],
            ],
            'series' => [
                [
                    'name' => 'FTP watts',
                    'color' => [
                        '#E34902',
                    ],
                    'type' => 'line',
                    'smooth' => false,
                    'yAxisIndex' => 0,
                    'label' => [
                        'show' => false,
                    ],
                    'lineStyle' => [
                        'width' => 1,
                    ],
                    'symbolSize' => 6,
                    'showSymbol' => true,
                    'data' => [
                        ...$this->ftps->map(
                            fn (Ftp $ftp): array => [
                                $ftp->getSetOn()->format('Y-m-d'),
                                $ftp->getFtp(),
                            ],
                        ),
                        $this->ftps->getLast() && $this->now->format('Y-m-d') != $this->ftps->getLast()->getSetOn()->format('Y-m-d') ?
                        [
                            $this->now->format('Y-m-d'),
                            $this->ftps->getLast()->getFtp(),
                        ] : [],
                    ],
                ],
                $this->ftps->getFirst()?->getRelativeFtp() ? [
                    'name' => 'FTP w/kg',
                    'type' => 'line',
                    'smooth' => false,
                    'color' => [
                        '#3AA272',
                    ],
                    'yAxisIndex' => 1,
                    'label' => [
                        'show' => false,
                    ],
                    'lineStyle' => [
                        'width' => 1,
                    ],
                    'symbolSize' => 6,
                    'showSymbol' => true,
                    'data' => [
                        ...$this->ftps->map(
                            fn (Ftp $ftp): array => [
                                $ftp->getSetOn()->format('Y-m-d'),
                                $ftp->getRelativeFtp(),
                            ]
                        ),
                        $this->ftps->getLast() && $this->now->format('Y-m-d') != $this->ftps->getLast()->getSetOn()->format('Y-m-d') ?
                        [
                            $this->now->format('Y-m-d'),
                            $this->ftps->getLast()->getRelativeFtp(),
                        ] : [],
                    ],
                ] : [],
            ],
        ];
    }
}
