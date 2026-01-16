<?php

namespace App\Tests\Domain\Integration\AI\Chat;

use App\Domain\Athlete\Athlete;
use App\Domain\Athlete\AthleteRepository;
use App\Domain\Integration\AI\Chat\ChatMessageId;
use App\Domain\Integration\AI\Chat\ChatRepository;
use App\Domain\Integration\AI\Chat\DbalChatRepository;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use NeuronAI\Chat\Enums\MessageRole;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;

class DbalChatRepositoryTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private ChatRepository $chatRepository;
    private MockObject $messageIdFactory;

    public function testAddAndGetHistory(): void
    {
        $this->getContainer()->get(AthleteRepository::class)->save(Athlete::create([
            'id' => 100,
            'birthDate' => '1989-08-14',
            'firstname' => 'robin',
        ]));

        $this->chatRepository->add(
            ChatMessageBuilder::fromDefaults()
                ->withMessageId(ChatMessageId::fromUnprefixed('test'))
                ->withMessage('User Message')
                ->withMessageRole(MessageRole::USER)
                ->withFirstLetterOfFirstName('r')
                ->build()
        );

        $this->chatRepository->add(
            ChatMessageBuilder::fromDefaults()
                ->withMessageId(ChatMessageId::fromUnprefixed('test-2'))
                ->withMessage('Assistant Message')
                ->withMessageRole(MessageRole::ASSISTANT)
                ->withFirstLetterOfFirstName('r')
                ->build()
        );

        $this->assertEquals(
            [
                ChatMessageBuilder::fromDefaults()
                    ->withMessageId(ChatMessageId::fromUnprefixed('test'))
                    ->withMessage('User Message')
                    ->withMessageRole(MessageRole::USER)
                    ->withFirstLetterOfFirstName('r')
                    ->build(),
                ChatMessageBuilder::fromDefaults()
                    ->withMessageId(ChatMessageId::fromUnprefixed('test-2'))
                    ->withMessage('Assistant Message')
                    ->withMessageRole(MessageRole::ASSISTANT)
                    ->withFirstLetterOfFirstName('r')
                    ->build(),
            ],
            $this->chatRepository->findAll()
        );
    }

    public function testCreate(): void
    {
        $this->getContainer()->get(AthleteRepository::class)->save(Athlete::create([
            'id' => 100,
            'birthDate' => '1989-08-14',
            'firstname' => 'robin',
        ]));

        $message = $this->chatRepository->buildMessage('The message', MessageRole::USER);
        $this->assertEquals(
            'The message',
            $message->getMessage(),
        );
        $this->assertEquals(
            MessageRole::USER,
            $message->getMessageRole(),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->chatRepository = new DbalChatRepository(
            $this->getConnection(),
            null,
            $this->getContainer()->get(AthleteRepository::class),
            PausedClock::on(SerializableDateTime::fromString('2019-08-14')),
        );
    }
}
