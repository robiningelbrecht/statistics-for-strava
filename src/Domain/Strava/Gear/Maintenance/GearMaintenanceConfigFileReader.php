<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;

final readonly class GearMaintenanceConfigFileReader
{
    public const string CONFIG_FILE_NAME = 'gear-maintenance.config.yml';

    public function __construct(
        private FilesystemOperator $defaultStorage,
    ) {
    }

    public function read(): ?string
    {
        try {
            return $this->defaultStorage->read(self::CONFIG_FILE_NAME);
        } catch (FilesystemException|UnableToReadFile) {
        }

        return null;
    }
}
