<?php

declare(strict_types=1);

namespace App\Domain\Athlete\HeartRateZone;

final class InvalidHeartZoneConfiguration extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf(
            'config/app/config.yaml athlete.heartRateZones: %s',
            $message,
        );
        parent::__construct($message, $code, $previous);
    }
}
