<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Chat\AddChatMessage;

use App\Infrastructure\CQRS\Command\DomainCommand;
use NeuronAI\Chat\Enums\MessageRole;

final readonly class AddChatMessage extends DomainCommand
{
    public function __construct(
        private string $message,
        private MessageRole $messageRole,
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getMessageRole(): MessageRole
    {
        return $this->messageRole;
    }
}
