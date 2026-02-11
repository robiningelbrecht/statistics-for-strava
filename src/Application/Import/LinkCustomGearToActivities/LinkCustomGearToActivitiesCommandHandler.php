<?php

declare(strict_types=1);

namespace App\Application\Import\LinkCustomGearToActivities;

use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Gear\CustomGear\CustomGear;
use App\Domain\Gear\CustomGear\CustomGearConfig;
use App\Domain\Gear\CustomGear\CustomGearRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;

final readonly class LinkCustomGearToActivitiesCommandHandler implements CommandHandler
{
    public function __construct(
        private CustomGearRepository $customGearRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private ActivityRepository $activityRepository,
        private ActivityIdRepository $activityIdRepository,
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

        $customGears = $this->customGearRepository->findAll();
        $allCustomGearTags = $customGears->map(static fn (CustomGear $customGear): string => $customGear->getTag());
        $activityIdsWithoutStravaGear = $this->activityIdRepository->findAllWithoutStravaGear();

        $activitiesWithCustomGearTag = [];
        $activitiesWithoutCustomGearTag = [];

        foreach ($activityIdsWithoutStravaGear as $activityId) {
            $activity = $this->activityRepository->find($activityId);
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

            // Make sure any previous linked custom gear is removed.
            $this->activityWithRawDataRepository->update(ActivityWithRawData::fromState(
                activity: $activity->withEmptyGear(),
                rawData: $activityWithRawData->getRawData()
            ));
        }

        /** @var CustomGear $customGear */
        foreach ($customGears as $customGear) {
            $customGear = $customGear->withDistance(Meter::zero());
            $activitiesTaggedWithCustomGear = $activitiesWithCustomGearTag[$customGear->getTag()] ?? [];

            /** @var Activity $activity */
            foreach ($activitiesTaggedWithCustomGear as $activity) {
                $activityWithRawData = $this->activityWithRawDataRepository->find($activity->getId());

                // Link activity to custom gear.
                $activity = $activity->withGear($customGear->getId());

                // Keep track of the distance for the custom gear.
                $customGear = $customGear->withDistance(Kilometer::from(
                    $customGear->getDistance()->toFloat() + $activity->getDistance()->toFloat())->toMeter()
                );

                $this->activityWithRawDataRepository->update(ActivityWithRawData::fromState(
                    activity: $activity,
                    rawData: $activityWithRawData->getRawData()
                ));
            }

            $this->customGearRepository->save($customGear);
        }
    }
}
