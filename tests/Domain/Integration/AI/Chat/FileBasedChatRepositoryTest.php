<?php

namespace App\Tests\Domain\Integration\AI\Chat;

use App\Domain\Integration\AI\Chat\ChatMessage;
use App\Domain\Integration\AI\Chat\ChatRepository;
use App\Domain\Integration\AI\Chat\FileBasedChatRepository;
use App\Domain\Strava\Athlete\Athlete;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Tests\ContainerTestCase;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\Messages\Message;
use PHPUnit\Framework\MockObject\MockObject;

class FileBasedChatRepositoryTest extends ContainerTestCase
{
    private ChatRepository $chatRepository;
    private MockObject $chatHistory;

    public function testGetHistory(): void
    {
        $this->getContainer()->get(AthleteRepository::class)->save(Athlete::create([
            'id' => 100,
            'birthDate' => '1989-08-14',
            'firstname' => 'robin',
        ]));

        $this->chatHistory
            ->expects($this->once())
            ->method('getMessages')
            ->willReturn([
                Message::make(MessageRole::USER, 'User message'),
                Message::make(MessageRole::ASSISTANT, 'Assistant message'),
                Message::make(MessageRole::USER, ''),
                Message::make(MessageRole::USER, ['lol']),
            ]);

        $this->assertEquals(
            [
                new ChatMessage(
                    message: 'User message',
                    userProfilePictureUrl: null,
                    firstLetterOfFirstName: 'r',
                    isUserMessage: true,
                ),
                new ChatMessage(
                    message: 'Assistant message',
                    userProfilePictureUrl: null,
                    firstLetterOfFirstName: 'r',
                    isUserMessage: false,
                ),
            ],
            $this->chatRepository->getHistory(),
        );
    }

    public function testCreate(): void
    {
        $this->getContainer()->get(AthleteRepository::class)->save(Athlete::create([
            'id' => 100,
            'birthDate' => '1989-08-14',
            'firstname' => 'robin',
        ]));

        $this->assertEquals(
            new ChatMessage(
                message: 'The message',
                userProfilePictureUrl: null,
                firstLetterOfFirstName: 'r',
                isUserMessage: true,
            ),
            $this->chatRepository->create('The message', true),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->chatRepository = new FileBasedChatRepository(
            $this->chatHistory = $this->createMock(ChatHistoryInterface::class),
            null,
            $this->getContainer()->get(AthleteRepository::class),
        );
    }
}
