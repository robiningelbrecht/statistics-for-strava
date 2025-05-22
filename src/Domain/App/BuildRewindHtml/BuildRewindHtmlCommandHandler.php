<?php

declare(strict_types=1);

namespace App\Domain\App\BuildRewindHtml;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\Image\ImageRepository;
use App\Domain\Strava\Activity\SportType\SportTypes;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Rewind\ActivityCountPerMonthChart;
use App\Domain\Strava\Rewind\ActivityLocationsChart;
use App\Domain\Strava\Rewind\ActivityStartTimesChart;
use App\Domain\Strava\Rewind\DailyActivitiesChart;
use App\Domain\Strava\Rewind\DistancePerMonthChart;
use App\Domain\Strava\Rewind\ElevationPerMonthChart;
use App\Domain\Strava\Rewind\FindActiveDays\FindActiveDays;
use App\Domain\Strava\Rewind\FindActivityCountPerMonth\FindActivityCountPerMonth;
use App\Domain\Strava\Rewind\FindActivityLocations\FindActivityLocations;
use App\Domain\Strava\Rewind\FindActivityStartTimesPerHour\FindActivityStartTimesPerHour;
use App\Domain\Strava\Rewind\FindAvailableRewindYears\FindAvailableRewindYears;
use App\Domain\Strava\Rewind\FindDistancePerMonth\FindDistancePerMonth;
use App\Domain\Strava\Rewind\FindElevationPerMonth\FindElevationPerMonth;
use App\Domain\Strava\Rewind\FindMovingTimePerDay\FindMovingTimePerDay;
use App\Domain\Strava\Rewind\FindMovingTimePerGear\FindMovingTimePerGear;
use App\Domain\Strava\Rewind\FindMovingTimePerSportType\FindMovingTimePerSportType;
use App\Domain\Strava\Rewind\FindPersonalRecordsPerMonth\FindPersonalRecordsPerMonth;
use App\Domain\Strava\Rewind\FindSocialsMetrics\FindSocialsMetrics;
use App\Domain\Strava\Rewind\FindStreaks\FindStreaks;
use App\Domain\Strava\Rewind\MovingTimePerGearChart;
use App\Domain\Strava\Rewind\MovingTimePerSportTypeChart;
use App\Domain\Strava\Rewind\PersonalRecordsPerMonthChart;
use App\Domain\Strava\Rewind\RestDaysVsActiveDaysChart;
use App\Domain\Strava\Rewind\RewindItem;
use App\Domain\Strava\Rewind\RewindItems;
use App\Domain\Strava\Rewind\RewindItemsPerYear;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\Year;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildRewindHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private GearRepository $gearRepository,
        private ImageRepository $imageRepository,
        private QueryBus $queryBus,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildRewindHtml);

        $now = $command->getCurrentDateTime();
        $findAvailableRewindYearsResponse = $this->queryBus->ask(new FindAvailableRewindYears($now));
        $availableRewindYears = $findAvailableRewindYearsResponse->getAvailableRewindYears();

        if (count($availableRewindYears) <= 1) {
            // No years to compare with.
            $this->buildStorage->write(
                'rewind.html',
                $this->twig->load('html/rewind/rewind-empty.html.twig')->render([
                    'now' => $now,
                ]),
            );

            return;
        }

        $gears = $this->gearRepository->findAll();

        $rewindItemsPerYear = RewindItemsPerYear::empty();
        foreach ($availableRewindYears as $availableRewindYearLeft) {
            $randomImage = null;
            try {
                $randomImage = $this->imageRepository->findRandomFor(
                    sportTypes: SportTypes::thatSupportImagesForStravaRewind(),
                    year: $availableRewindYearLeft
                );
            } catch (EntityNotFound) {
            }

            $longestActivity = $this->activityRepository->findLongestActivityForYear($availableRewindYearLeft);
            $leafletMap = $longestActivity->getLeafletMap();

            $findMovingTimePerDayResponse = $this->queryBus->ask(new FindMovingTimePerDay($availableRewindYearLeft));
            $findMovingTimePerSportTypeResponse = $this->queryBus->ask(new FindMovingTimePerSportType($availableRewindYearLeft));
            $socialsMetricsResponse = $this->queryBus->ask(new FindSocialsMetrics($availableRewindYearLeft));
            $streaksResponse = $this->queryBus->ask(new FindStreaks($availableRewindYearLeft));
            $distancePerMonthResponse = $this->queryBus->ask(new FindDistancePerMonth($availableRewindYearLeft));
            $elevationPerMonthResponse = $this->queryBus->ask(new FindElevationPerMonth($availableRewindYearLeft));
            $activeDaysResponse = $this->queryBus->ask(new FindActiveDays($availableRewindYearLeft));

            $totalActivityCount = $findMovingTimePerDayResponse->getTotalActivityCount();
            /** @var RewindItems $rewindItems */
            $rewindItems = RewindItems::empty()
                ->add(RewindItem::from(
                    icon: 'calendar',
                    title: $this->translator->trans('Daily activities'),
                    subTitle: $this->translator->trans('{numberOfActivities} activities in {year}', [
                        '{numberOfActivities}' => $totalActivityCount,
                        '{year}' => $availableRewindYearLeft,
                    ]),
                    content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                        'chart' => Json::encode(DailyActivitiesChart::create(
                            movingTimePerDay: $findMovingTimePerDayResponse->getMovingTimePerDay(),
                            year: $availableRewindYearLeft,
                            translator: $this->translator,
                        )->build()),
                    ]),
                ))
                ->add(RewindItem::from(
                    icon: 'tools',
                    title: $this->translator->trans('Gear'),
                    subTitle: $this->translator->trans('Total hours spent per gear'),
                    content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                        'chart' => Json::encode(MovingTimePerGearChart::create(
                            movingTimePerGear: $this->queryBus->ask(new FindMovingTimePerGear($availableRewindYearLeft))->getMovingTimePerGear(),
                            gears: $gears,
                        )->build()),
                    ]),
                ))
                ->add(RewindItem::from(
                    icon: 'trophy',
                    title: $this->translator->trans('Longest activity (h)'),
                    subTitle: $longestActivity->getName(),
                    content: $this->twig->render('html/rewind/rewind-biggest-activity.html.twig', [
                        'activity' => $longestActivity,
                        'leaflet' => $leafletMap ? [
                            'routes' => [$longestActivity->getPolyline()],
                            'map' => $leafletMap,
                        ] : null,
                    ])
                ))
                ->add(RewindItem::from(
                    icon: 'medal',
                    title: $this->translator->trans('PRs'),
                    subTitle: $this->translator->trans('PRs achieved per month'),
                    content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                        'chart' => Json::encode(PersonalRecordsPerMonthChart::create(
                            personalRecordsPerMonth: $this->queryBus->ask(new FindPersonalRecordsPerMonth($availableRewindYearLeft))->getPersonalRecordsPerMonth(),
                            year: $availableRewindYearLeft,
                            translator: $this->translator,
                        )->build()),
                    ]),
                ))
                ->add(RewindItem::from(
                    icon: 'thumbs-up',
                    title: $this->translator->trans('Socials'),
                    subTitle: $this->translator->trans('Total kudos and comments received'),
                    content: $this->twig->render('html/rewind/rewind-socials.html.twig', [
                        'kudoCount' => $socialsMetricsResponse->getKudoCount(),
                        'commentCount' => $socialsMetricsResponse->getCommentCount(),
                    ])
                ))
                ->add(RewindItem::from(
                    icon: 'rocket',
                    title: $this->translator->trans('Distance'),
                    subTitle: $this->translator->trans('Total distance per month'),
                    content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                        'chart' => Json::encode(DistancePerMonthChart::create(
                            distancePerMonth: $distancePerMonthResponse->getDistancePerMonth(),
                            year: $availableRewindYearLeft,
                            unitSystem: $this->unitSystem,
                            translator: $this->translator,
                        )->build()),
                    ]),
                    totalMetric: $distancePerMonthResponse->getTotalDistance()->toUnitSystem($this->unitSystem)->toInt(),
                    totalMetricLabel: $this->unitSystem->distanceSymbol(),
                ))
                ->add(RewindItem::from(
                    icon: 'mountain',
                    title: $this->translator->trans('Elevation'),
                    subTitle: $this->translator->trans('Total elevation per month'),
                    content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                        'chart' => Json::encode(ElevationPerMonthChart::create(
                            elevationPerMonth: $elevationPerMonthResponse->getElevationPerMonth(),
                            year: $availableRewindYearLeft,
                            unitSystem: $this->unitSystem,
                            translator: $this->translator,
                        )->build()),
                    ]),
                    totalMetric: $elevationPerMonthResponse->getTotalElevation()->toUnitSystem($this->unitSystem)->toInt(),
                    totalMetricLabel: $this->unitSystem->elevationSymbol(),
                ))->add(RewindItem::from(
                    icon: 'watch',
                    title: $this->translator->trans('Total hours'),
                    subTitle: $this->translator->trans('Total hours spent per sport type'),
                    content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                        'chart' => Json::encode(MovingTimePerSportTypeChart::create(
                            movingTimePerSportType: $findMovingTimePerSportTypeResponse->getMovingTimePerSportType(),
                            translator: $this->translator,
                        )->build()),
                    ]),
                    totalMetric: (int) round($findMovingTimePerSportTypeResponse->getTotalMovingTime() / 3600),
                    totalMetricLabel: $this->translator->trans('hours')
                ))
                ->add(RewindItem::from(
                    icon: 'fire',
                    title: $this->translator->trans('Streaks'),
                    subTitle: $this->translator->trans('Longest streaks'),
                    content: $this->twig->render('html/rewind/rewind-streaks.html.twig', [
                        'dayStreak' => $streaksResponse->getDayStreak(),
                        'weekStreak' => $streaksResponse->getWeekStreak(),
                        'monthStreak' => $streaksResponse->getMonthStreak(),
                    ])
                ))
                ->add(RewindItem::from(
                    icon: 'bed',
                    title: $this->translator->trans('Rest days'),
                    subTitle: $this->translator->trans('Rest days vs. active days'),
                    content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                        'chart' => Json::encode(RestDaysVsActiveDaysChart::create(
                            numberOfActiveDays: $activeDaysResponse->getNumberOfActiveDays(),
                            numberOfRestDays: $availableRewindYearLeft->getNumberOfDays() - $activeDaysResponse->getNumberOfActiveDays(),
                            translator: $this->translator,
                        )->build()),
                    ]),
                    totalMetric: (int) round(($activeDaysResponse->getNumberOfActiveDays() / $availableRewindYearLeft->getNumberOfDays()) * 100),
                    totalMetricLabel: '%'
                ))->add(RewindItem::from(
                    icon: 'clock',
                    title: $this->translator->trans('Start times'),
                    subTitle: $this->translator->trans('Activity start times'),
                    content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                        'chart' => Json::encode(ActivityStartTimesChart::create(
                            activityStartTimes: $this->queryBus->ask(new FindActivityStartTimesPerHour($availableRewindYearLeft))->getActivityStartTimesPerHour(),
                            translator: $this->translator
                        )->build()),
                    ]),
                ))
                ->add(RewindItem::from(
                    icon: 'muscle',
                    title: $this->translator->trans('Activity count'),
                    subTitle: $this->translator->trans('Number of activities per month'),
                    content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                        'chart' => Json::encode(ActivityCountPerMonthChart::create(
                            activityCountPerMonth: $this->queryBus->ask(new FindActivityCountPerMonth($availableRewindYearLeft))->getActivityCountPerMonth(),
                            year: $availableRewindYearLeft,
                            translator: $this->translator,
                        )->build()),
                    ]),
                    totalMetric: $totalActivityCount,
                    totalMetricLabel: $this->translator->trans('activities'),
                ));

            if ($activityLocations = $this->queryBus->ask(new FindActivityLocations($availableRewindYearLeft))->getActivityLocations()) {
                $rewindItems->add(RewindItem::from(
                    icon: 'globe',
                    title: $this->translator->trans('Activity locations'),
                    subTitle: $this->translator->trans('Locations over the globe'),
                    content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                        'chart' => Json::encode(ActivityLocationsChart::create($activityLocations)->build()),
                    ]),
                ));
            }
            if ($randomImage) {
                $rewindItems->add(RewindItem::from(
                    icon: 'image',
                    title: $this->translator->trans('Photo'),
                    subTitle: $randomImage->getActivity()->getStartDate()->translatedFormat('M d, Y'),
                    content: $this->twig->render('html/rewind/rewind-random-image.html.twig', [
                        'image' => $randomImage,
                    ]),
                ));
            }

            $rewindItemsPerYear->add(
                rewindYear: $availableRewindYearLeft,
                items: $rewindItems,
            );

            $render = [
                'now' => $now,
                'availableRewindYears' => $availableRewindYears,
                'activeRewindYear' => $availableRewindYearLeft,
                'rewindItems' => $rewindItems,
            ];

            $this->buildStorage->write(
                sprintf('rewind/%s.html', $availableRewindYearLeft),
                $this->twig->load('html/rewind/rewind.html.twig')->render($render),
            );

            if ($availableRewindYears->getFirst() == $availableRewindYearLeft) {
                $this->buildStorage->write(
                    'rewind.html',
                    $this->twig->load('html/rewind/rewind.html.twig')->render($render),
                );
            }
        }

        $years = $availableRewindYears->toArray();
        foreach ($availableRewindYears as $availableRewindYearLeft) {
            $defaultRewindYearToCompareWith = $years[0] != $availableRewindYearLeft ? $years[0] : $years[1];

            foreach ($availableRewindYears as $availableRewindYearRight) {
                if ($availableRewindYearLeft == $availableRewindYearRight) {
                    continue;
                }

                $render = $this->twig->load('html/rewind/rewind-compare.html.twig')->render([
                    'availableRewindYears' => $availableRewindYears,
                    'availableRewindYearsToCompareWith' => $availableRewindYears->filter(fn (Year $year) => $year != $availableRewindYearLeft && $year != $availableRewindYearRight),
                    'activeRewindYearLeft' => $availableRewindYearLeft,
                    'activeRewindYearRight' => $availableRewindYearRight,
                    'rewindItemsLeft' => $rewindItemsPerYear->getForYear($availableRewindYearLeft),
                    'rewindItemsRight' => $rewindItemsPerYear->getForYear($availableRewindYearRight),
                ]);

                if ($availableRewindYearRight == $defaultRewindYearToCompareWith) {
                    $this->buildStorage->write(
                        sprintf('rewind/%s/compare.html', $availableRewindYearLeft),
                        $render
                    );
                }

                $this->buildStorage->write(
                    sprintf('rewind/%s/compare/%s.html', $availableRewindYearLeft, $availableRewindYearRight),
                    $render
                );
            }
        }
    }
}
