<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon\Cron;

final class InvalidCronConfig extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf(
            'config/app/config.yaml daemon.cron: %s',
            $message,
        );
        parent::__construct($message, $code, $previous);
    }
}
