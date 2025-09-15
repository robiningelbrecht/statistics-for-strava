<?php

declare(strict_types=1);

namespace App\Domain\Rewind;

use App\Domain\Calendar\Month;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class PersonalRecordsPerMonthChart
{
    private function __construct(
        /** @var array<int, array{0: int, 1: int}> */
        private array $personalRecordsPerMonth,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<int, array{0: int, 1: int}> $personalRecordsPerMonth
     */
    public static function create(
        array $personalRecordsPerMonth,
        TranslatorInterface $translator,
    ): self {
        return new self(
            personalRecordsPerMonth: $personalRecordsPerMonth,
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
            [$monthNumber, $personalRecords] = $personalRecordsPerMonth;
            $data[] = [$monthNumber - 1, $personalRecords];
        }

        for ($monthNumber = 1; $monthNumber <= 12; ++$monthNumber) {
            // We don't care about the year, we only need this to display the month label.
            $date = sprintf('%s-%02d-01', 2025, $monthNumber);
            $month = Month::fromDate(SerializableDateTime::fromString($date));
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
