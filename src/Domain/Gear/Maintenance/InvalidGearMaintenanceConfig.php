<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

final class InvalidGearMaintenanceConfig extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf(
            'config/app/gear-maintenance.yaml: %s',
            $message,
        );
        parent::__construct($message, $code, $previous);
    }
}
