<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Chat;

use App\BuildApp\ProfilePictureUrl;
use App\Domain\Athlete\AthleteRepository;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;
use NeuronAI\Chat\Enums\MessageRole;

final readonly class DbalChatRepository extends DbalRepository implements ChatRepository
{
    public function __construct(
        Connection $connection,
        private ?ProfilePictureUrl $profilePictureUrl,
        private AthleteRepository $athleteRepository,
        private Clock $clock,
    ) {
        parent::__construct($connection);
    }

    public function add(ChatMessage $message): void
    {
        $sql = 'INSERT INTO ChatMessage (messageId, message, messageRole, `on`) 
                VALUES (:messageId, :message, :messageRole, :on)';

        $this->connection->executeStatement($sql, [
            'messageId' => $message->getMessageId(),
            'message' => $message->getMessage(),
            'messageRole' => $message->getMessageRole()->value,
            'on' => $message->getOn(),
        ]);
    }

    public function getHistory(): array
    {
        $results = $this->connection->executeQuery('SELECT * FROM ChatMessage ORDER BY `on` ASC')
            ->fetchAllAssociative();

        $history = [];
        foreach ($results as $result) {
            $history[] = new ChatMessage(
                messageId: ChatMessageId::fromString($result['messageId']),
                message: nl2br((string) $result['message']),
                messageRole: MessageRole::from($result['messageRole']),
                on: SerializableDateTime::fromString($result['on']),
            )->withUserProfilePictureUrl($this->profilePictureUrl)
                ->withFirstLetterOfFirstName(substr((string) $this->athleteRepository->find()->getName(), 0, 1));
        }

        return $history;
    }

    public function buildMessage(string $message, MessageRole $messageRole): ChatMessage
    {
        return new ChatMessage(
            messageId: ChatMessageId::random(),
            message: $message,
            messageRole: $messageRole,
            on: $this->clock->getCurrentDateTimeImmutable()
        )->withUserProfilePictureUrl($this->profilePictureUrl)
            ->withFirstLetterOfFirstName(substr((string) $this->athleteRepository->find()->getName(), 0, 1));
    }
}
