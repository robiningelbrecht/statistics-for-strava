<?php

namespace App\Tests\Domain\Integration\AI\Chat\AddChatMessage;

use App\Domain\Integration\AI\Chat\AddChatMessage\AddChatMessage;
use App\Domain\Integration\AI\Chat\AddChatMessage\AddChatMessageCommandHandler;
use App\Domain\Integration\AI\Chat\ChatRepository;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use NeuronAI\Chat\Enums\MessageRole;
use Spatie\Snapshots\MatchesSnapshots;

class AddChatMessageCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;
    private AddChatMessageCommandHandler $addChatMessageCommandHandler;

    public function testHandle(): void
    {
        $this->addChatMessageCommandHandler->handle(new AddChatMessage(
            message: 'Le message',
            messageRole: MessageRole::USER
        ));

        $results = $this->getConnection()->executeQuery('SELECT * FROM ChatMessage')->fetchAllAssociative();
        foreach ($results as &$result) {
            unset($result['messageId']);
        }

        $this->assertMatchesJsonSnapshot($results);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->addChatMessageCommandHandler = new AddChatMessageCommandHandler(
            $this->getContainer()->get(ChatRepository::class),
            PausedClock::on(SerializableDateTime::fromString('2025-01-01'))
        );
    }
}
