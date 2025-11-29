<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingGoals;

final class InvalidTrainingGoalsConfiguration extends \RuntimeException
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
