<?php

declare(strict_types=1);

namespace App\Domain\Activity\UpdateActivity;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\Image\ActivityImagePath;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use League\Flysystem\FilesystemOperator;

final readonly class UpdateActivityCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private FilesystemOperator $fileStorage,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof UpdateActivity);

        $activityWithRawData = $this->activityRepository->findWithRawData($command->getActivityId());
        $activity = $activityWithRawData
            ->getActivity()
            ->withName($command->getName())
            ->withSportType($command->getSportType())
            ->withDescription($command->getDescription())
            ->withDeviceName($command->getDeviceName())
            ->withGear($command->getGearId())
            ->withCommute($command->isCommute());

        $newImages = $command->getNewImages();
        $removedImages = $command->getRemovedImages();

        $imagePathsThatNeedRemoval = [];
        if ([] !== $newImages || [] !== $removedImages) {
            $removedLocalImagePaths = array_map(
                static fn (ActivityRemovedImage $removedImage): string => $removedImage->getPath()->toLocalImagePath(),
                $removedImages
            );
            $isRemoved = static fn (ActivityImagePath $path): bool => in_array($path->toLocalImagePath(), $removedLocalImagePaths, true);

            $currentPaths = array_map(
                ActivityImagePath::fromLocalImagePath(...),
                $activity->getLocalImagePaths()
            );

            $retainedPaths = array_values(array_filter($currentPaths, static fn (ActivityImagePath $path): bool => !$isRemoved($path)));

            // Only files that were genuinely attached to this activity may be deleted from disk.
            $imagePathsThatNeedRemoval = array_map(
                static fn (ActivityImagePath $path): string => $path->toFileSystemPath(),
                array_filter($currentPaths, $isRemoved)
            );

            foreach ($newImages as $newImage) {
                $fileSystemPath = sprintf('activities/%s.%s', $this->uuidFactory->random(), $newImage->getFilename()->getExtension());
                $this->fileStorage->write($fileSystemPath, $newImage->getContent());
                $retainedPaths[] = ActivityImagePath::fromFileSystemPath($fileSystemPath);
            }

            $activity = $activity->withLocalImagePaths(array_map(
                static fn (ActivityImagePath $path): string => $path->toLocalImagePath(),
                $retainedPaths
            ));
        }

        $this->activityRepository->update(ActivityWithRawData::fromState(
            activity: $activity,
            rawData: $activityWithRawData->getRawData(),
        ));

        foreach ($imagePathsThatNeedRemoval as $fileSystemPath) {
            if ($this->fileStorage->fileExists($fileSystemPath)) {
                $this->fileStorage->delete($fileSystemPath);
            }
        }
    }
}
