<?php

namespace App\Application\Import\ImportActivities\Pipeline;

use App\Domain\Strava\Strava;
use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use League\Flysystem\FilesystemOperator;
use Uri\Rfc3986\Uri;

final readonly class DownloadActivityImages implements ActivityImportStep
{
    public function __construct(
        private Strava $strava,
        private FilesystemOperator $fileStorage,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function process(ActivityImportContext $context): ActivityImportContext
    {
        $activity = $context->getActivity() ?? throw new \RuntimeException('Activity not set on $context');
        $rawStravaData = $context->getRawStravaData();

        if (!$totalImageCount = ($rawStravaData['total_photo_count'] ?? 0)) {
            $activity->updateLocalImagePaths([]);

            return $context->withActivity($activity);
        }

        $shouldDownloadImages = $context->isNewActivity() || count($activity->getLocalImagePaths()) !== $totalImageCount;
        if (!$shouldDownloadImages) {
            return $context->withActivity($activity);
        }

        try {
            $fileSystemPaths = [];
            $photos = $this->strava->getActivityPhotos($activity->getId());
            foreach ($photos as $photo) {
                if (empty($photo['urls'][5000])) {
                    continue;
                }

                $uri = new Uri($photo['urls'][5000]);
                $extension = pathinfo($uri->getPath(), PATHINFO_EXTENSION);

                $fileSystemPath = sprintf('activities/%s.%s', $this->uuidFactory->random(), $extension);
                $this->fileStorage->write(
                    $fileSystemPath,
                    $this->strava->downloadImage($photo['urls'][5000])
                );

                $fileSystemPaths[] = $fileSystemPath;
            }

            $activity->updateLocalImagePaths(array_map(
                fn (string $fileSystemPath): string => 'files/'.$fileSystemPath,
                $fileSystemPaths
            ));
        } catch (ClientException|RequestException) {
        }

        return $context->withActivity($activity);
    }
}
