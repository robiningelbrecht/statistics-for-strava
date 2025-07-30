<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Chat;

final class InvalidChatCommandsConfig extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf(
            'config/app/config.yaml integrations.ai.agent.commands: %s',
            $message,
        );
        parent::__construct($message, $code, $previous);
    }
}
