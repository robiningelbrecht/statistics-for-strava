<?php

namespace App\Application\Import\ImportActivities\Pipeline;

use App\Domain\Strava\Strava;
use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use League\Flysystem\FilesystemOperator;

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

        if (!$newTotalImageCount = ($rawStravaData['total_photo_count'] ?? 0)) {
            $activity->updateLocalImagePaths([]);
        }

        if ($context->isNewActivity() && $newTotalImageCount > 0 || $activity->getTotalImageCount() !== $newTotalImageCount && $newTotalImageCount > 0) {
            return $context->withActivity($activity);
        }

        try {
            $fileSystemPaths = [];
            $photos = $this->strava->getActivityPhotos($activity->getId());
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
