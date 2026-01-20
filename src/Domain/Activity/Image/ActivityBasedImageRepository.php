<?php

namespace App\Domain\Activity\Image;

use App\Application\Build\BuildPhotosHtml\HidePhotosForSportTypes;
use App\Domain\Activity\EnrichedActivities;
use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\Years;

final readonly class ActivityBasedImageRepository implements ImageRepository
{
    public function __construct(
        private EnrichedActivities $enrichedActivities,
        private HidePhotosForSportTypes $hidePhotosForSportTypes,
        private KernelProjectDir $kernelProjectDir,
    ) {
    }

    public function findAll(): Images
    {
        $images = Images::empty();
        $activities = $this->enrichedActivities->findAll();
        /** @var \App\Domain\Activity\Activity $activity */
        foreach ($activities as $activity) {
            if (0 === $activity->getTotalImageCount()) {
                continue;
            }

            if ($this->hidePhotosForSportTypes->has($activity->getSportType())) {
                continue;
            }

            foreach ($activity->getLocalImagePaths() as $localImagePath) {
                $absoluteImagePath = $this->kernelProjectDir.'/storage'.$localImagePath;
                $imageOrientation = ImageOrientation::LANDSCAPE;
                if (file_exists($absoluteImagePath) && ($info = @getimagesize($absoluteImagePath)) !== false) {
                    $imageOrientation = ImageOrientation::fromWidthAndHeight($info[0], $info[1]);
                }

                $images->add(Image::create(
                    imageLocation: $localImagePath,
                    activity: $activity,
                    orientation: $imageOrientation,
                ));
            }
        }

        return $images;
    }

    public function findRandomFor(SportTypes $sportTypes, Years $years): Image
    {
        $activities = $this->enrichedActivities->findAll()->toArray();
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

            $absoluteImagePath = $this->kernelProjectDir.'/storage'.$localImagePaths[$randomImageIndex];
            $imageOrientation = ImageOrientation::LANDSCAPE;
            if (file_exists($absoluteImagePath) && ($info = @getimagesize($absoluteImagePath)) !== false) {
                $imageOrientation = ImageOrientation::fromWidthAndHeight($info[0], $info[1]);
            }

            return Image::create(
                imageLocation: $localImagePaths[$randomImageIndex],
                activity: $activity,
                orientation: $imageOrientation,
            );
        }

        throw new EntityNotFound(sprintf('Random image not found'));
    }

    public function count(): int
    {
        $activities = $this->enrichedActivities->findAll();
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
