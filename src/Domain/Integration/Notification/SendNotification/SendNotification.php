<?php

declare(strict_types=1);

namespace App\Domain\Integration\Notification\SendNotification;

use App\Infrastructure\CQRS\Command\DomainCommand;

final readonly class SendNotification extends DomainCommand
{
    public function __construct(
        private string $title,
        private string $message,
        /** @var array<string> */
        private array $tags,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
