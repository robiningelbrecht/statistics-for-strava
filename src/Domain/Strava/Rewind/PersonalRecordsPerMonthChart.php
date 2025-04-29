<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind;

use App\Domain\Strava\Calendar\Month;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class PersonalRecordsPerMonthChart
{
    private function __construct(
        /** @var array<int, array{0: Month, 1: int}> */
        private array $personalRecordsPerMonth,
        private Year $year,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<int, array{0: Month, 1: int}> $personalRecordsPerMonth
     */
    public static function create(
        array $personalRecordsPerMonth,
        Year $year,
        TranslatorInterface $translator,
    ): self {
        return new self(
            personalRecordsPerMonth: $personalRecordsPerMonth,
            year: $year,
            translator: $translator
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $data = [];
        $xAxisLabels = [];

        foreach ($this->personalRecordsPerMonth as $personalRecordsPerMonth) {
            [$month, $personalRecords] = $personalRecordsPerMonth;
            $data[] = [$month->getMonth() - 1, $personalRecords];
        }

        for ($monthNumber = 1; $monthNumber <= 12; ++$monthNumber) {
            $month = Month::fromDate(SerializableDateTime::fromString(sprintf('%s-%02d-01', $this->year, $monthNumber)));
            $xAxisLabels[] = $month->getShortLabelWithoutYear();
        }

        return [
            'animation' => false,
            'backgroundColor' => null,
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'grid' => [
                'left' => '0%',
                'right' => '15px',
                'bottom' => '0%',
                'top' => '15px',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'data' => $xAxisLabels,
                    'boundaryGap' => false,
                    'axisTick' => [
                        'show' => false,
                    ],
                    'splitLine' => [
                        'show' => false,
                    ],
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'min' => 0,
                ],
            ],
            'series' => [
                [
                    'name' => $this->translator->trans('Personal Records'),
                    'color' => [
                        '#E34902',
                    ],
                    'label' => [
                        'show' => true,
                    ],
                    'areaStyle' => [
                        'opacity' => 0.3,
                        'color' => 'rgba(227, 73, 2, 0.3)',
                    ],
                    'type' => 'line',
                    'smooth' => false,
                    'lineStyle' => [
                        'width' => 2,
                    ],
                    'showSymbol' => true,
                    'data' => $data,
                ],
            ],
        ];
    }
}
