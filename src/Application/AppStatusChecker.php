<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Athlete\AthleteRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\FileSystem\PermissionChecker;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;

final readonly class AppStatusChecker
{
    public function __construct(
        private AthleteRepository $athleteRepository,
        private ActivityIdRepository $activityIdRepository,
        private PermissionChecker $fileSystemPermissionChecker,
    ) {
    }

    public function ensureIsReadyForStravaImport(): void
    {
        // The athlete is imported as part of the Strava import itself,
        // so it can not be a precondition here.
        $this->ensureFileSystemIsWritable();
    }

    public function ensureIsReadyForFileImport(): void
    {
        $this->ensureFileSystemIsWritable();
        $this->ensureAthleteCanBeLoaded();
    }

    public function ensureIsReadyForBuild(): void
    {
        $this->ensureAthleteCanBeLoaded();

        if ($this->activityIdRepository->count() <= 0) {
            throw AppIsNotReady::becauseNoActivitiesHaveBeenImportedYet();
        }
    }

    private function ensureFileSystemIsWritable(): void
    {
        try {
            $this->fileSystemPermissionChecker->ensureWriteAccess();
        } catch (UnableToWriteFile|UnableToCreateDirectory) {
            throw AppIsNotReady::becauseFileSystemIsNotWritable();
        }
    }

    private function ensureAthleteCanBeLoaded(): void
    {
        try {
            $this->athleteRepository->find();
        } catch (EntityNotFound) {
            throw AppIsNotReady::becauseAthleteHasNotBeenImportedYet();
        }
    }
}
