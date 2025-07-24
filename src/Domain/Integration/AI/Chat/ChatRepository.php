<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Chat;

use NeuronAI\Chat\Enums\MessageRole;

interface ChatRepository
{
    public function add(ChatMessage $message): void;

    /**
     * @return ChatMessage[]
     */
    public function getHistory(): array;

    public function buildMessage(string $message, MessageRole $messageRole): ChatMessage;
}
