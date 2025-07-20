<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Chat;

interface ChatRepository
{
    /**
     * @return ChatMessage[]
     */
    public function getHistory(): array;

    public function create(string $message, bool $isUserMessage): ChatMessage;
}
