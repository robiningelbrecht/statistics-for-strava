<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Chat;

use App\Domain\App\ProfilePictureUrl;

final readonly class ChatMessage
{
    public function __construct(
        private string $message,
        private ?ProfilePictureUrl $userProfilePictureUrl,
        private string $firstLetterOfFirstName,
        private bool $isUserMessage,
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getUserProfilePictureUrl(): ?ProfilePictureUrl
    {
        return $this->userProfilePictureUrl;
    }

    public function getFirstLetterOfFirstName(): string
    {
        return $this->firstLetterOfFirstName;
    }

    public function isUserMessage(): bool
    {
        return $this->isUserMessage;
    }
}
