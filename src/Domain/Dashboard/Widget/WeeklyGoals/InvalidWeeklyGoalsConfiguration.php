<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\WeeklyGoals;

final class InvalidWeeklyGoalsConfiguration extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf(
            'config/app/config.yaml dashboard.layout: %s',
            $message,
        );
        parent::__construct($message, $code, $previous);
    }
}
