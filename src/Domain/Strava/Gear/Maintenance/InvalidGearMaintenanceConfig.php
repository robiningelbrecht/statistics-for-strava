<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance;

final class InvalidGearMaintenanceConfig extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf(
            'storage/gear-maintenance/%s: %s',
            GearMaintenanceConfigFileReader::CONFIG_FILE_NAME,
            $message,
        );
        parent::__construct($message, $code, $previous);
    }
}
