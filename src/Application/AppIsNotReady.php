<?php

declare(strict_types=1);

namespace App\Application;

final class AppIsNotReady extends \RuntimeException
{
    public static function becauseAthleteHasNotBeenImportedYet(): self
    {
        return new self('Wait until your athlete has been imported before continuing');
    }

    public static function becauseNoActivitiesHaveBeenImportedYet(): self
    {
        return new self('Wait until at least one activity has been imported before building the app');
    }

    public static function becauseFileSystemIsNotWritable(): self
    {
        return new self('Make sure the container has write permissions to "storage/database" and "storage/files" on the host system');
    }
}
