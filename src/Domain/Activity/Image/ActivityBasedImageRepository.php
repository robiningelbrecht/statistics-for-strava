<?php

namespace App\Domain\Activity\Image;

use App\BuildApp\BuildPhotosHtml\HidePhotosForSportTypes;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\Years;

final readonly class ActivityBasedImageRepository implements ImageRepository
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private HidePhotosForSportTypes $hidePhotosForSportTypes,
    ) {
    }

    public function findAll(): Images
    {
        $images = Images::empty();
        $activities = $this->activityRepository->findAll();
        /** @var \App\Domain\Activity\Activity $activity */
        foreach ($activities as $activity) {
            if (0 === $activity->getTotalImageCount()) {
                continue;
            }

            if ($this->hidePhotosForSportTypes->has($activity->getSportType())) {
                continue;
            }

            foreach ($activity->getLocalImagePaths() as $localImagePath) {
                $images->add(Image::create(
                    imageLocation: $localImagePath,
                    activity: $activity
                ));
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
            if ($this->hidePhotosForSportTypes->has($activity->getSportType())) {
                continue;
            }
            $totalImageCount += $activity->getTotalImageCount();
        }

        return $totalImageCount;
    }
}
