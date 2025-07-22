<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

final readonly class ServerSentEvent implements \Stringable
{
    public function __construct(
        private string $eventName,
        private string $data,
    ) {
    }

    public function __toString(): string
    {
        return implode('', [
            sprintf('event: %s', $this->eventName).PHP_EOL,
            'data: '.str_replace("\n", '\\n', $this->data)."\n\n",
        ]);
    }
}
