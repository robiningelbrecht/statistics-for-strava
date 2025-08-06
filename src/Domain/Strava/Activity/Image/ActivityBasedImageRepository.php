<?php

namespace App\Domain\Strava\Activity\Image;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\SportType\SportTypes;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\Years;

final readonly class ActivityBasedImageRepository implements ImageRepository
{
    public function __construct(
        private ActivityRepository $activityRepository,
    ) {
    }

    public function findAll(): Images
    {
        $images = Images::empty();
        $activities = $this->activityRepository->findAll();
        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($activities as $activity) {
            if (0 === $activity->getTotalImageCount()) {
                continue;
            }

            foreach ($activity->getLocalImagePaths() as $localImagePath) {
                $images->add(
                    sportType: $activity->getSportType(),
                    image: Image::create(
                        imageLocation: $localImagePath,
                        activity: $activity
                    )
                );
            }
        }

        return $images;
    }

    public function findRandomFor(SportTypes $sportTypes, Years $years): Image
    {
        $activities = $this->activityRepository->findAll()->toArray();
        shuffle($activities);

        foreach ($activities as $activity) {
            if (!$years->has(Year::fromInt($activity->getStartDate()->getYear()))) {
                continue;
            }

            if (!$sportTypes->has($activity->getSportType())) {
                continue;
            }

            if (!$localImagePaths = $activity->getLocalImagePaths()) {
                continue;
            }

            $randomImageIndex = array_rand($localImagePaths);

            return Image::create(
                imageLocation: $localImagePaths[$randomImageIndex],
                activity: $activity,
            );
        }

        throw new EntityNotFound(sprintf('Random image not found'));
    }

    public function count(): int
    {
        $activities = $this->activityRepository->findAll();
        $totalImageCount = 0;

        foreach ($activities as $activity) {
            $totalImageCount += $activity->getTotalImageCount();
        }

        return $totalImageCount;
    }
}
