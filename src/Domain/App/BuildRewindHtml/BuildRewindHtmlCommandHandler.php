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
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
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
        $response = $this->queryBus->ask(new FindAvailableRewindYears($now));
        $availableRewindYears = $response->getAvailableRewindYears();

        $gears = $this->gearRepository->findAll();

        foreach ($availableRewindYears as $availableRewindYear) {
            $randomImage = null;
            try {
                $randomImage = $this->imageRepository->findRandomFor(
                    sportTypes: SportTypes::thatSupportImagesForStravaRewind(),
                    year: $availableRewindYear
                );
            } catch (EntityNotFound) {
            }

            $longestActivity = $this->activityRepository->findLongestActivityForYear($availableRewindYear);
            $leafletMap = $longestActivity->getLeafletMap();

            $findMovingTimePerDayResponse = $this->queryBus->ask(new FindMovingTimePerDay($availableRewindYear));
            $findMovingTimePerSportTypeResponse = $this->queryBus->ask(new FindMovingTimePerSportType($availableRewindYear));
            $socialsMetricsResponse = $this->queryBus->ask(new FindSocialsMetrics($availableRewindYear));
            $streaksResponse = $this->queryBus->ask(new FindStreaks($availableRewindYear));
            $distancePerMonthResponse = $this->queryBus->ask(new FindDistancePerMonth($availableRewindYear));
            $elevationPerMonthResponse = $this->queryBus->ask(new FindElevationPerMonth($availableRewindYear));
            $activeDaysResponse = $this->queryBus->ask(new FindActiveDays($availableRewindYear));

            $totalActivityCount = $findMovingTimePerDayResponse->getTotalActivityCount();

            $render = [
                'now' => $now,
                'availableRewindYears' => $availableRewindYears,
                'activeRewindYear' => $availableRewindYear,
                'rewindItems' => [
                    RewindItem::from(
                        icon: 'calendar',
                        title: $this->translator->trans('Daily activities'),
                        subTitle: $this->translator->trans('{numberOfActivities} activities in {year}', [
                            '{numberOfActivities}' => $totalActivityCount,
                            '{year}' => $availableRewindYear,
                        ]),
                        content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                            'chart' => Json::encode(DailyActivitiesChart::create(
                                movingTimePerDay: $findMovingTimePerDayResponse->getMovingTimePerDay(),
                                year: $availableRewindYear,
                                translator: $this->translator,
                            )->build()),
                        ]),
                    ),
                    RewindItem::from(
                        icon: 'tools',
                        title: $this->translator->trans('Gear'),
                        subTitle: $this->translator->trans('Total hours spent per gear'),
                        content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                            'chart' => Json::encode(MovingTimePerGearChart::create(
                                movingTimePerGear: $this->queryBus->ask(new FindMovingTimePerGear($availableRewindYear))->getMovingTimePerGear(),
                                gears: $gears,
                            )->build()),
                        ]),
                    ),
                    RewindItem::from(
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
                    ),
                    RewindItem::from(
                        icon: 'medal',
                        title: $this->translator->trans('PRs'),
                        subTitle: $this->translator->trans('PRs achieved per month'),
                        content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                            'chart' => Json::encode(PersonalRecordsPerMonthChart::create(
                                personalRecordsPerMonth: $this->queryBus->ask(new FindPersonalRecordsPerMonth($availableRewindYear))->getPersonalRecordsPerMonth(),
                                year: $availableRewindYear,
                                translator: $this->translator,
                            )->build()),
                        ]),
                    ),
                    RewindItem::from(
                        icon: 'thumbs-up',
                        title: $this->translator->trans('Socials'),
                        subTitle: $this->translator->trans('Total kudos and comments received'),
                        content: $this->twig->render('html/rewind/rewind-socials.html.twig', [
                            'kudoCount' => $socialsMetricsResponse->getKudoCount(),
                            'commentCount' => $socialsMetricsResponse->getCommentCount(),
                        ])
                    ),
                    RewindItem::from(
                        icon: 'rocket',
                        title: $this->translator->trans('Distance'),
                        subTitle: $this->translator->trans('Total distance per month'),
                        content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                            'chart' => Json::encode(DistancePerMonthChart::create(
                                distancePerMonth: $distancePerMonthResponse->getDistancePerMonth(),
                                year: $availableRewindYear,
                                unitSystem: $this->unitSystem,
                                translator: $this->translator,
                            )->build()),
                        ]),
                        totalMetric: $distancePerMonthResponse->getTotalDistance()->toUnitSystem($this->unitSystem)->toInt(),
                        totalMetricLabel: $this->unitSystem->distanceSymbol(),
                    ),
                    RewindItem::from(
                        icon: 'mountain',
                        title: $this->translator->trans('Elevation'),
                        subTitle: $this->translator->trans('Total elevation per month'),
                        content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                            'chart' => Json::encode(ElevationPerMonthChart::create(
                                elevationPerMonth: $elevationPerMonthResponse->getElevationPerMonth(),
                                year: $availableRewindYear,
                                unitSystem: $this->unitSystem,
                                translator: $this->translator,
                            )->build()),
                        ]),
                        totalMetric: $elevationPerMonthResponse->getTotalElevation()->toUnitSystem($this->unitSystem)->toInt(),
                        totalMetricLabel: $this->unitSystem->elevationSymbol(),
                    ),
                    RewindItem::from(
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
                    ),
                    RewindItem::from(
                        icon: 'fire',
                        title: $this->translator->trans('Streaks'),
                        subTitle: $this->translator->trans('Longest streaks'),
                        content: $this->twig->render('html/rewind/rewind-streaks.html.twig', [
                            'dayStreak' => $streaksResponse->getDayStreak(),
                            'weekStreak' => $streaksResponse->getWeekStreak(),
                            'monthStreak' => $streaksResponse->getMonthStreak(),
                        ])
                    ),
                    RewindItem::from(
                        icon: 'bed',
                        title: $this->translator->trans('Rest days'),
                        subTitle: $this->translator->trans('Rest days vs. active days'),
                        content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                            'chart' => Json::encode(RestDaysVsActiveDaysChart::create(
                                numberOfActiveDays: $activeDaysResponse->getNumberOfActiveDays(),
                                numberOfRestDays: $availableRewindYear->getNumberOfDays() - $activeDaysResponse->getNumberOfActiveDays(),
                                translator: $this->translator,
                            )->build()),
                        ]),
                        totalMetric: (int) round(($activeDaysResponse->getNumberOfActiveDays() / $availableRewindYear->getNumberOfDays()) * 100),
                        totalMetricLabel: '%'
                    ),
                    RewindItem::from(
                        icon: 'clock',
                        title: $this->translator->trans('Start times'),
                        subTitle: $this->translator->trans('Activity start times'),
                        content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                            'chart' => Json::encode(ActivityStartTimesChart::create(
                                activityStartTimes: $this->queryBus->ask(new FindActivityStartTimesPerHour($availableRewindYear))->getActivityStartTimesPerHour(),
                                translator: $this->translator
                            )->build()),
                        ]),
                    ),
                    RewindItem::from(
                        icon: 'muscle',
                        title: $this->translator->trans('Activity count'),
                        subTitle: $this->translator->trans('Number of activities per month'),
                        content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                            'chart' => Json::encode(ActivityCountPerMonthChart::create(
                                activityCountPerMonth: $this->queryBus->ask(new FindActivityCountPerMonth($availableRewindYear))->getActivityCountPerMonth(),
                                year: $availableRewindYear,
                                translator: $this->translator,
                            )->build()),
                        ]),
                        totalMetric: $totalActivityCount,
                        totalMetricLabel: $this->translator->trans('activities'),
                    ),
                ],
            ];

            if ($activityLocations = $this->queryBus->ask(new FindActivityLocations($availableRewindYear))->getActivityLocations()) {
                $render['rewindItems'][] = RewindItem::from(
                    icon: 'globe',
                    title: $this->translator->trans('Activity locations'),
                    subTitle: $this->translator->trans('Locations over the globe'),
                    content: $this->twig->render('html/rewind/rewind-chart.html.twig', [
                        'chart' => Json::encode(ActivityLocationsChart::create($activityLocations)->build()),
                    ]),
                );
            }

            if ($randomImage) {
                $render['rewindItems'][] = RewindItem::from(
                    icon: 'image',
                    title: $this->translator->trans('Photo'),
                    subTitle: $randomImage->getActivity()->getStartDate()->translatedFormat('M d, Y'),
                    content: $this->twig->render('html/rewind/rewind-random-image.html.twig', [
                        'image' => $randomImage,
                    ]),
                );
            }

            if ($availableRewindYears->getFirst() == $availableRewindYear) {
                $this->buildStorage->write(
                    'rewind.html',
                    $this->twig->load('html/rewind/rewind.html.twig')->render($render),
                );
            }

            $this->buildStorage->write(
                sprintf('rewind/%s/compare.html', $availableRewindYear),
                $this->twig->load('html/rewind/rewind-compare.html.twig')->render($render),
            );

            $this->buildStorage->write(
                sprintf('rewind/%s.html', $availableRewindYear),
                $this->twig->load('html/rewind/rewind.html.twig')->render($render),
            );
        }
    }
}
