<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Training;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class TrainingLoadChart
{
    public const int NUMBER_OF_DAYS_TO_DISPLAY = 42;

    private function __construct(
        private TrainingMetrics $trainingMetrics,
        private TranslatorInterface $translator,
        private SerializableDateTime $now,
    ) {
    }

    public static function create(
        TrainingMetrics $trainingMetrics,
        TranslatorInterface $translator,
        SerializableDateTime $now,
    ): self {
        return new self(
            trainingMetrics: $trainingMetrics,
            translator: $translator,
            now: $now,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $start = SerializableDateTime::fromString($this->now->format('Y-m-d 00:00:00'))
                ->modify('-'.(self::NUMBER_OF_DAYS_TO_DISPLAY - 1).' days');
        $period = new \DatePeriod(
            $start,
            new \DateInterval('P1D'),
            SerializableDateTime::fromString($this->now->format('Y-m-d 23:59:59'))
        );

        $formattedDates = [];
        foreach ($period as $date) {
            $formattedDates[] = SerializableDateTime::fromDateTimeImmutable($date)->translatedFormat('M d');
        }
        $tsbValues = $this->trainingMetrics->getTsbValuesForXLastDays(self::NUMBER_OF_DAYS_TO_DISPLAY);

        return [
            'tooltip' => [
                'trigger' => 'axis',
                'axisPointer' => [
                    'link' => [['xAxisIndex' => 'all']],
                    'label' => ['backgroundColor' => '#6a7985'],
                ],
            ],
            'legend' => [
                'show' => true,
            ],
            'axisPointer' => [
                'link' => ['xAxisIndex' => 'all'],
            ],
            'grid' => [
                [
                    'left' => '50px',
                    'right' => '50px',
                    'top' => '40px',
                    'height' => '63%',
                    'containLabel' => false,
                ],
                [
                    'left' => '50px',
                    'right' => '50px',
                    'top' => '75%',
                    'height' => '20%',
                    'bottom' => '0px',
                    'containLabel' => false,
                ],
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'gridIndex' => 0,
                    'data' => $formattedDates,
                    'boundaryGap' => true,
                    'axisLine' => ['onZero' => false],
                    'axisLabel' => ['show' => false],
                    'axisTick' => ['show' => false],
                ],
                [
                    'type' => 'category',
                    'gridIndex' => 1,
                    'data' => $formattedDates,
                    'boundaryGap' => true,
                    'position' => 'bottom',
                    'axisLabel' => ['show' => true],
                    'axisTick' => ['show' => true],
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => $this->translator->trans('Daily TRIMP'),
                    'nameLocation' => 'middle',
                    'nameGap' => 35,
                    'gridIndex' => 1,
                    'position' => 'left',
                    'splitLine' => ['show' => true],
                    'axisLine' => ['show' => true, 'lineStyle' => ['color' => '#cccccc']],
                    'minInterval' => 1,
                    'axisLabel',
                ],
                [
                    'type' => 'value',
                    'name' => $this->translator->trans('Load (CTL/ATL)'),
                    'nameLocation' => 'middle',
                    'nameGap' => 35,
                    'gridIndex' => 0,
                    'position' => 'left',
                    'alignTicks' => true,
                    'axisLine' => ['show' => true, 'lineStyle' => ['color' => '#cccccc']],
                    'axisLabel' => ['formatter' => 'toInteger'],
                    'splitLine' => ['show' => true],
                    'minInterval' => 1,
                ],
                [
                    'type' => 'value',
                    'name' => $this->translator->trans('Form (TSB)'),
                    'nameLocation' => 'middle',
                    'nameGap' => 35,
                    'gridIndex' => 0,
                    'position' => 'right',
                    'alignTicks' => true,
                    'max' => (int) ceil(max(25, ...$tsbValues)),
                    'min' => (int) floor(min(-35, ...$tsbValues)),
                    'minInterval' => 1,
                    'axisLine' => ['show' => true, 'lineStyle' => ['color' => '#cccccc']],
                    'axisLabel' => ['formatter' => 'toInteger'],
                    'splitLine' => ['show' => false],
                ],
            ],
            'series' => [
                [
                    'name' => $this->translator->trans('CTL (Fitness)'),
                    'type' => 'line',
                    'data' => $this->trainingMetrics->getCtlValuesForXLastDays(self::NUMBER_OF_DAYS_TO_DISPLAY),
                    'smooth' => true,
                    'symbol' => 'none',
                    'xAxisIndex' => 0,
                    'yAxisIndex' => 1,
                ],
                [
                    'name' => $this->translator->trans('ATL (Fatigue)'),
                    'type' => 'line',
                    'data' => $this->trainingMetrics->getAtlValuesForXLastDays(self::NUMBER_OF_DAYS_TO_DISPLAY),
                    'smooth' => true,
                    'symbol' => 'none',
                    'xAxisIndex' => 0,
                    'yAxisIndex' => 1,
                ],
                [
                    'name' => $this->translator->trans('TSB (Form)'),
                    'type' => 'line',
                    'data' => $tsbValues,
                    'smooth' => true,
                    'symbol' => 'none',
                    'xAxisIndex' => 0,
                    'yAxisIndex' => 2,
                    'markLine' => [
                        'silent' => true,
                        'lineStyle' => ['color' => '#333', 'type' => 'dashed'],
                        'label' => [
                            'position' => 'insideEndTop',
                        ],
                        'data' => [
                            [
                                'yAxis' => 15,
                                'label' => ['formatter' => $this->translator->trans('Taper sweet-spot (+15)')],
                            ],
                            [
                                'yAxis' => -10,
                                'label' => ['formatter' => $this->translator->trans('Build zone (–10)')],
                            ],
                            [
                                'yAxis' => -30,
                                'label' => ['formatter' => $this->translator->trans('Over-fatigued (–30)')],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => $this->translator->trans('Daily TRIMP'),
                    'type' => 'bar',
                    'data' => $this->trainingMetrics->getTrimpValuesForXLastDays(self::NUMBER_OF_DAYS_TO_DISPLAY),
                    'itemStyle' => ['color' => '#FC4C02'],
                    'barWidth' => '60%',
                    'xAxisIndex' => 1,
                    'yAxisIndex' => 0,
                    'emphasis' => ['itemStyle' => ['opacity' => 0.8]],
                ],
            ],
        ];
    }
}
