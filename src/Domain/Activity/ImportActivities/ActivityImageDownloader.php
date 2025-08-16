<?php

declare(strict_types=1);

namespace App\Domain\Activity\ImportActivities;

use App\Domain\Activity\ActivityId;
use App\Domain\Strava\Strava;
use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use League\Flysystem\FilesystemOperator;

final readonly class ActivityImageDownloader
{
    public function __construct(
        private Strava $strava,
        private FilesystemOperator $fileStorage,
        private UuidFactory $uuidFactory,
    ) {
    }

    /**
     * @return string[]
     */
    public function downloadImages(ActivityId $activityId): array
    {
        $downloadedImagePaths = [];
        $photos = $this->strava->getActivityPhotos($activityId);
        foreach ($photos as $photo) {
            if (empty($photo['urls'][5000])) {
                continue;
            }

            /** @var string $urlPath */
            $urlPath = parse_url((string) $photo['urls'][5000], PHP_URL_PATH);
            $extension = pathinfo($urlPath, PATHINFO_EXTENSION);
            $fileSystemPath = sprintf('activities/%s.%s', $this->uuidFactory->random(), $extension);
            $this->fileStorage->write(
                $fileSystemPath,
                $this->strava->downloadImage($photo['urls'][5000])
            );

            $downloadedImagePaths[] = $fileSystemPath;
        }

        return $downloadedImagePaths;
    }
}
