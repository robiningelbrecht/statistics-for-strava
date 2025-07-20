<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Chat;

use App\Domain\App\ProfilePictureUrl;
use App\Domain\Strava\Athlete\AthleteRepository;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\History\ChatHistoryInterface;

final readonly class FileBasedChatRepository implements ChatRepository
{
    public function __construct(
        private ChatHistoryInterface $chatHistory,
        private ?ProfilePictureUrl $profilePictureUrl,
        private AthleteRepository $athleteRepository,
    ) {
    }

    public function getHistory(): array
    {
        $history = [];

        foreach ($this->chatHistory->getMessages() as $message) {
            if (!$message->getContent()) {
                continue;
            }
            $history[] = new ChatMessage(
                message: $message->getContent(),
                userProfilePictureUrl: $this->profilePictureUrl,
                firstLetterOfFirstName: substr((string) $this->athleteRepository->find()->getName(), 0, 1),
                isUserMessage: $message->getRole() === MessageRole::USER->value,
            );
        }

        return $history;
    }

    public function create(string $message, bool $isUserMessage): ChatMessage
    {
        return new ChatMessage(
            message: $message,
            userProfilePictureUrl: $this->profilePictureUrl,
            firstLetterOfFirstName: $message,
            isUserMessage: $isUserMessage
        );
    }
}
