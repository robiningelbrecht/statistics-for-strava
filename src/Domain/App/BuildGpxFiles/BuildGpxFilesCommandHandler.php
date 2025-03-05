<?php

declare(strict_types=1);

namespace App\Domain\App\BuildGpxFiles;

use App\Domain\Strava\Activity\GpxSerializer;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use League\Flysystem\FilesystemOperator;

final readonly class BuildGpxFilesCommandHandler implements CommandHandler
{
    public function __construct(
        private GpxSerializer $serializer,
        private ActivityStreamRepository $activityStreamRepository,
        private FilesystemOperator $fileStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildGpxFiles);

        $activityIds = $this->activityStreamRepository->findActivityIdsByStreamType(StreamType::TIME);
        foreach ($activityIds as $activityId) {
            $gpxFileLocation = sprintf('activities/gpx/%s.gpx', $activityId);
            if ($this->fileStorage->fileExists($gpxFileLocation)) {
                continue;
            }
            if (!$serializedGpx = $this->serializer->serialize($activityId)) {
                continue;
            }
            $this->fileStorage->write(
                $gpxFileLocation,
                $serializedGpx,
            );
        }
    }
}
