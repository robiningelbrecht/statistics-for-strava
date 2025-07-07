<?php

namespace App\Tests\Domain\Integration\AI\Chat;

use App\Domain\Integration\AI\Chat\FlysystemChatHistory;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\FileSystem\provideAssertFileSystem;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Messages\Message;

class FlysystemChatHistoryTest extends ContainerTestCase
{
    use provideAssertFileSystem;

    public function testStoreMessage(): void
    {
        $this->getContainer()->get('default.storage')->write('storage/neuron-agent.chat', Json::encode([]));

        $flysystemChatHistory = new FlysystemChatHistory(
            $this->getContainer()->get('default.storage')
        );

        $flysystemChatHistory->addMessage(new Message(
            MessageRole::ASSISTANT,
            'This is my message'
        ));
        $flysystemChatHistory->addMessage(new Message(
            MessageRole::USER,
            'Hello?'
        ));
        $this->assertFileSystemWrites($this->getContainer()->get('default.storage'));
    }

    public function testItShouldClear(): void
    {
        $flysystemChatHistory = new FlysystemChatHistory(
            $this->getContainer()->get('default.storage')
        );

        $flysystemChatHistory->addMessage(new Message(
            MessageRole::ASSISTANT,
            'This is my message'
        ));
        $flysystemChatHistory->addMessage(new Message(
            MessageRole::USER,
            'Hello?'
        ));

        $this->assertTrue($this->getContainer()->get('default.storage')->has('storage/neuron-agent.chat'));
        $flysystemChatHistory->flushAll();
        $this->assertFalse($this->getContainer()->get('default.storage')->has('storage/neuron-agent.chat'));
    }
}
