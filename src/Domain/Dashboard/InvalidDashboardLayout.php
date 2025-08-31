<?php

declare(strict_types=1);

namespace App\Domain\Dashboard;

final class InvalidDashboardLayout extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf(
            'config/app/config.yaml appearance.dashboard.layout: %s',
            $message,
        );
        parent::__construct($message, $code, $previous);
    }
}
