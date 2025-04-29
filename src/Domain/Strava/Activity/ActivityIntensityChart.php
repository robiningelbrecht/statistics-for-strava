<?php

namespace App\Domain\Strava\Activity;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ActivityIntensityChart
{
    private SerializableDateTime $fromDate;
    private SerializableDateTime $toDate;

    private function __construct(
        private Activities $activities,
        private ActivityIntensity $activityIntensity,
        private TranslatorInterface $translator,
        private SerializableDateTime $now,
    ) {
        $fromDate = SerializableDateTime::fromString($this->now->modify('-11 months')->format('Y-m-01'));
        $this->fromDate = $fromDate;
        $toDate = SerializableDateTime::fromString($this->now->format('Y-m-t 23:59:59'));
        $this->toDate = $toDate;
    }

    public static function create(
        Activities $activities,
        ActivityIntensity $activityIntensity,
        TranslatorInterface $translator,
        SerializableDateTime $now,
    ): self {
        return new self(
            activities: $activities,
            activityIntensity: $activityIntensity,
            translator: $translator,
            now: $now,
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
            'legend' => [
                'show' => true,
            ],
            'title' => [
                'left' => 'center',
                'text' => sprintf('%s - %s', $this->fromDate->translatedFormat('M Y'), $this->toDate->translatedFormat('M Y')),
            ],
            'tooltip' => [
                'trigger' => 'item',
            ],
            'visualMap' => [
                'type' => 'piecewise',
                'selectedMode' => false,
                'left' => 'center',
                'bottom' => 0,
                'orient' => 'horizontal',
                'pieces' => [
                    [
                        'min' => 0,
                        'max' => 0,
                        'color' => '#cdd9e5',
                        'label' => $this->translator->trans('No activities'),
                    ],
                    [
                        'min' => 0.01,
                        'max' => 33,
                        'color' => '#68B34B',
                        'label' => $this->translator->trans('Low').' (0 - 33)',
                    ],
                    [
                        'min' => 33.01,
                        'max' => 66,
                        'color' => '#FAB735',
                        'label' => $this->translator->trans('Medium').' (34 - 66)',
                    ],
                    [
                        'min' => 66.01,
                        'max' => 100,
                        'color' => '#FF8E14',
                        'label' => $this->translator->trans('High').' (67 - 100)',
                    ],
                    [
                        'min' => 100.01,
                        'color' => '#FF0C0C',
                        'label' => $this->translator->trans('Very high').' (> 100)',
                    ],
                ],
            ],
            'calendar' => [
                'left' => 40,
                'cellSize' => [
                    'auto',
                    13,
                ],
                'range' => [$this->fromDate->format('Y-m-d'), $this->toDate->format('Y-m-d')],
                'itemStyle' => [
                    'borderWidth' => 3,
                    'opacity' => 0,
                ],
                'splitLine' => [
                    'show' => false,
                ],
                'yearLabel' => [
                    'show' => false,
                ],
                'dayLabel' => [
                    'firstDay' => 1,
                    'align' => 'right',
                    'fontSize' => 10,
                    'nameMap' => [
                        $this->translator->trans('Sun'),
                        $this->translator->trans('Mon'),
                        $this->translator->trans('Tue'),
                        $this->translator->trans('Wed'),
                        $this->translator->trans('Thu'),
                        $this->translator->trans('Fri'),
                        $this->translator->trans('Sat'),
                    ],
                ],
            ],
            'series' => [
                'type' => 'heatmap',
                'coordinateSystem' => 'calendar',
                'data' => $this->getData(),
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    private function getData(): array
    {
        $activities = $this->activities->filterOnDateRange(
            fromDate: $this->fromDate,
            toDate: $this->toDate
        );

        $data = $rawData = [];
        /** @var Activity $activity */
        foreach ($activities as $activity) {
            if (!$intensity = $this->activityIntensity->calculate($activity)) {
                continue;
            }

            $day = $activity->getStartDate()->format('Y-m-d');
            if (!array_key_exists($day, $rawData)) {
                $rawData[$day] = 0;
            }

            $rawData[$day] += $intensity;
        }

        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod(
            $this->fromDate,
            $interval,
            $this->toDate,
        );

        foreach ($period as $dt) {
            $day = $dt->format('Y-m-d');
            if (!array_key_exists($day, $rawData)) {
                $data[] = [$day, 0];

                continue;
            }

            $data[] = [$day, $rawData[$day]];
        }

        return $data;
    }
}
