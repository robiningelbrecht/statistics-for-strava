<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Chat;

use App\Domain\Integration\AI\Chat\AddChatMessage\AddChatMessage;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
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
        private readonly CommandBus $commandBus,
    ) {
        parent::__construct();
    }

    protected function storeMessage(Message $message): ChatHistoryInterface
    {
        if (!empty($message->getContent()) && is_string($message->getContent())) {
            $this->commandBus->dispatch(new AddChatMessage(
                message: $message->getContent(),
                messageRole: MessageRole::from($message->getRole()),
            ));
        }

        return parent::storeMessage($message);
    }
}
