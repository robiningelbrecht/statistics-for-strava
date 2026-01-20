<?php

namespace App\Tests;

use Symfony\Component\Console\Style\SymfonyStyle;

class SpySymfonyStyleOutput extends SymfonyStyle implements \Stringable
{
    private array $messages = [];

    #[Override]
    #[\Override]
    public function writeln(string|iterable $messages, int $type = self::OUTPUT_NORMAL): void
    {
        if (!is_iterable($messages)) {
            $messages = [$messages];
        }
        $this->messages = [...$this->messages, ...$messages];
    }

    #[Override]
    #[\Override]
    public function write(string|iterable $messages, bool $newline = false, int $type = self::OUTPUT_NORMAL): void
    {
        if (!is_iterable($messages)) {
            $messages = [$messages];
        }
        $this->messages = [...$this->messages, ...$messages];
    }

    public function __toString(): string
    {
        return implode(PHP_EOL, $this->messages);
    }
}
