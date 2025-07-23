<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Chat;

use App\Infrastructure\Time\Clock\Clock;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\History\InMemoryChatHistory as BaseInMemoryChatHistory;
use NeuronAI\Chat\Messages\Message;

/**
 * @codeCoverageIgnore
 */
final class SFSChatHistory extends BaseInMemoryChatHistory
{
    public function __construct(
        private readonly ChatRepository $chatRepository,
        private readonly Clock $clock,
    ) {
        parent::__construct();
    }

    protected function storeMessage(Message $message): ChatHistoryInterface
    {
        if (!empty($message->getContent()) && is_string($message->getContent())) {
            $this->chatRepository->add(new ChatMessage(
                messageId: ChatMessageId::random(),
                message: $message->getContent(),
                messageRole: MessageRole::from($message->getRole()),
                on: $this->clock->getCurrentDateTimeImmutable(),
            ));
        }

        return parent::storeMessage($message);
    }
}
