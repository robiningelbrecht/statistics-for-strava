<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Chat;

use App\Infrastructure\Serialization\Json;
use League\Flysystem\FilesystemOperator;
use NeuronAI\Chat\History\AbstractChatHistory;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\Messages\Message;

final class FlysystemChatHistory extends AbstractChatHistory
{
    private const string FILE_PATH = 'storage/neuron-agent.chat';

    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
        int $contextWindow = 50000,
    ) {
        parent::__construct($contextWindow);

        if ($this->defaultStorage->fileExists(self::FILE_PATH)) {
            $messages = Json::decode($this->defaultStorage->read(self::FILE_PATH)) ?? [];
            $this->history = $this->deserializeMessages($messages);
        }
    }

    protected function storeMessage(Message $message): ChatHistoryInterface
    {
        $this->defaultStorage->write(self::FILE_PATH, Json::encode($this->getMessages()));

        return $this;
    }

    public function removeOldestMessage(): ChatHistoryInterface
    {
        $this->defaultStorage->write(self::FILE_PATH, Json::encode($this->getMessages()));

        return $this;
    }

    protected function clear(): ChatHistoryInterface
    {
        $this->defaultStorage->delete(self::FILE_PATH);

        return $this;
    }
}
