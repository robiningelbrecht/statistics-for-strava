<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;

final readonly class GearMaintenanceConfigFileReader
{
    public const string CONFIG_FILE_NAME = 'config.yml';

    public function __construct(
        private FilesystemOperator $gearMaintenanceStorage,
    ) {
    }

    public function read(): ?string
    {
        if (!$this->gearMaintenanceStorage->fileExists(self::CONFIG_FILE_NAME)) {
            return null;
        }
        try {
            return $this->gearMaintenanceStorage->read(self::CONFIG_FILE_NAME);
        } catch (FilesystemException|UnableToReadFile) {
        }

        return null;
    }
}
