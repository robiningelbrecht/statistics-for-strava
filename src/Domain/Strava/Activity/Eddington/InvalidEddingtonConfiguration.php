<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Eddington;

final class InvalidEddingtonConfiguration extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf(
            'config/app/config.yaml metrics.eddington: %s',
            $message,
        );
        parent::__construct($message, $code, $previous);
    }
}
