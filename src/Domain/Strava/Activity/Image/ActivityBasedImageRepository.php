<?php

namespace App\Domain\Strava\Activity\Image;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\SportType\SportTypes;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\Year;

final readonly class ActivityBasedImageRepository implements ImageRepository
{
    public function __construct(
        private ActivityRepository $activityRepository,
    ) {
    }

    public function findAll(): Images
    {
        $images = [];
        $activities = $this->activityRepository->findAll();
        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($activities as $activity) {
            if (0 === $activity->getTotalImageCount()) {
                continue;
            }
            $images = [
                ...$images,
                ...array_map(
                    fn (string $path) => Image::create(
                        imageLocation: $path,
                        activity: $activity
                    ),
                    $activity->getLocalImagePaths()
                ),
            ];
        }

        return Images::fromArray($images);
    }

    public function findRandomFor(SportTypes $sportTypes, Year $year): Image
    {
        $activities = $this->activityRepository->findAll()->toArray();
        shuffle($activities);

        foreach ($activities as $activity) {
            if ($activity->getStartDate()->getYear() !== $year->toInt()) {
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

        throw new EntityNotFound(sprintf('Random image for %s not found', $year));
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
