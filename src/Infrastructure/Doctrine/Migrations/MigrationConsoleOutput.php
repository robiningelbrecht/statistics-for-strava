<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations;

use Symfony\Component\Console\Output\NullOutput;

final class MigrationConsoleOutput extends NullOutput
{
    private iterable $messages = [];

    public function writeln(string|iterable $messages, int $options = self::OUTPUT_NORMAL): void
    {
        if (is_string($messages)) {
            $messages = [$messages];
        }
        $this->messages = [...$this->messages, ...$messages];
    }

    public function write(string|iterable $messages, bool $newline = false, int $options = self::OUTPUT_NORMAL): void
    {
        $this->writeln($messages, $options);
    }

    public function getDisplay(): string
    {
        return implode(PHP_EOL, $this->messages);
    }
}
