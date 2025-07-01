<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI;

final class InvalidAIConfiguration extends \RuntimeException
{
    public function __construct(string $key, string $message, int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf(
            '%s: %s',
            $key,
            $message,
        );
        parent::__construct($message, $code, $previous);
    }
}
