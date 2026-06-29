<?php

namespace App\Tests\Infrastructure\CQRS\Command\Deserialize;

use App\Infrastructure\CQRS\Command\Deserialize\DeserializableCommand;
use App\Infrastructure\CQRS\Command\Deserialize\ProvidesCommandName;
use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\ValueObject\String\Url;

final readonly class TestDeserializableCommand extends DomainCommand implements DeserializableCommand
{
    use ProvidesCommandName;

    public function __construct(
        private string $message,
        private Url $url,
    ) {
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            message: $payload['message'],
            url: Url::fromString($payload['url']),
        );
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }
}
