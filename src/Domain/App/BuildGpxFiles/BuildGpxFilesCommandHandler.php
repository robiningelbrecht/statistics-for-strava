<?php

declare(strict_types=1);

namespace App\Domain\App\BuildGpxFiles;

use App\Domain\Strava\Activity\GpxSerializer;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;
use League\Flysystem\FilesystemOperator;

final readonly class BuildGpxFilesCommandHandler implements CommandHandler
{
    public function __construct(
        private GpxSerializer $serializer,
        private ActivityStreamRepository $activityStreamRepository,
        private FilesystemOperator $filesystem,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildGpxFiles);

        $timeStreams = $this->activityStreamRepository->findByStreamType(StreamType::TIME);
        foreach ($timeStreams as $timeStream) {
            if (!$serializedGpx = $this->serializer->serialize($timeStream->getActivityId())) {
                continue;
            }
            $this->filesystem->write(
                sprintf('storage/files/activities/gpx/%s.gpx', $timeStream->getActivityId()),
                $serializedGpx,
            );
        }
    }
}
