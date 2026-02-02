<?php

declare(strict_types=1);

namespace App\Application\Import\LinkCustomGearToActivities;

use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Gear\CustomGear\CustomGear;
use App\Domain\Gear\CustomGear\CustomGearConfig;
use App\Domain\Gear\CustomGear\CustomGearRepository;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;

final readonly class LinkCustomGearToActivitiesCommandHandler implements CommandHandler
{
    public function __construct(
        private ImportedGearRepository $importedGearRepository,
        private CustomGearRepository $customGearRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private ActivityRepository $activityRepository,
        private CustomGearConfig $customGearConfig,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof LinkCustomGearToActivities);

        if (!$this->customGearConfig->isFeatureEnabled()) {
            return;
        }

        $command->getOutput()->writeln('Linking custom gear to activities...');

        $importedGears = $this->importedGearRepository->findAll();
        $customGears = $this->customGearRepository->findAll();
        $allCustomGearTags = $customGears->map(static fn (CustomGear $customGear): string => $customGear->getTag());
        $activities = $this->activityRepository->findAll();

        // Filter out activities that have a Strava gear linked,
        // we only want to link custom gear to activities that do not have a Strava gear.
        $activitiesWithoutStravaGear = $activities->filter(
            fn (Activity $activity): bool => !$activity->getGearId() || !$importedGears->getByGearId($activity->getGearId())
        );
        $activitiesWithCustomGearTag = [];
        $activitiesWithoutCustomGearTag = [];

        foreach ($activitiesWithoutStravaGear as $activity) {
            $matchedCustomGearTagsForActivity = array_filter(
                $allCustomGearTags,
                static fn (string $customGearTag): bool => 1 === preg_match('/(^|\s)'.preg_quote($customGearTag, '/').'(\s|$)/', $activity->getOriginalName())
            );

            if (count($matchedCustomGearTagsForActivity) > 1) {
                $command->getOutput()->writeln(sprintf(
                    '<error>Activity "%s" has multiple custom gear tags, this is not supported.</error>',
                    $activity->getId()
                ));

                return;
            }

            if (1 === count($matchedCustomGearTagsForActivity)) {
                $activitiesWithCustomGearTag[reset($matchedCustomGearTagsForActivity)][] = $activity;
            } else {
                $activitiesWithoutCustomGearTag[] = $activity;
            }
        }

        foreach ($activitiesWithoutCustomGearTag as $activity) {
            $activityWithRawData = $this->activityWithRawDataRepository->find($activity->getId());
            $activityRawData = $activityWithRawData->getRawData();

            // Make sure any previous linked gear is removed.
            $this->activityWithRawDataRepository->update(ActivityWithRawData::fromState(
                activity: $activity->withEmptyGear(),
                rawData: $activityRawData
            ));
        }

        /** @var CustomGear $customGear */
        foreach ($customGears as $customGear) {
            $customGear = $customGear->withDistance(Meter::zero());
            $activitiesTaggedWithCustomGear = $activitiesWithCustomGearTag[$customGear->getTag()] ?? [];

            /** @var Activity $activity */
            foreach ($activitiesTaggedWithCustomGear as $activity) {
                $activityWithRawData = $this->activityWithRawDataRepository->find($activity->getId());
                $activityRawData = $activityWithRawData->getRawData();

                // Link activity to custom gear.
                $activity = $activity->withGear($customGear->getId());

                // Keep track of the distance for the custom gear.
                $customGear = $customGear->withDistance(Kilometer::from(
                    $customGear->getDistance()->toFloat() + $activity->getDistance()->toFloat())->toMeter()
                );

                $this->activityWithRawDataRepository->update(ActivityWithRawData::fromState(
                    activity: $activity,
                    rawData: $activityRawData
                ));
            }

            $this->customGearRepository->save($customGear);
        }
    }
}
