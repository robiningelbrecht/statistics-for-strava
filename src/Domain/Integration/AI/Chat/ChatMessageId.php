<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Chat;

use App\Infrastructure\ValueObject\Identifier\Identifier;

final readonly class ChatMessageId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'chatMessage-';
    }
}
