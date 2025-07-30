<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Chat;

final readonly class ChatCommands implements \JsonSerializable
{
    private function __construct(
        /** @var array<string, string> */
        private array $commands,
    ) {
    }

    /**
     * @param array<int, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $processedCommands = [];
        foreach ($config as $commandConfig) {
            foreach (['command', 'message'] as $requiredKey) {
                if (array_key_exists($requiredKey, $commandConfig)) {
                    continue;
                }
                throw new InvalidChatCommandsConfig(sprintf('"%s" property is required', $requiredKey));
            }

            if (!is_string($commandConfig['command'])) {
                throw new InvalidChatCommandsConfig(sprintf('command must be a string'));
            }
            if (!is_string($commandConfig['message'])) {
                throw new InvalidChatCommandsConfig(sprintf('message must be a string'));
            }
            if (empty($commandConfig['command']) || empty($commandConfig['message'])) {
                throw new InvalidChatCommandsConfig('command and message cannot be empty.');
            }
            if (str_starts_with($commandConfig['command'], '/')) {
                throw new InvalidChatCommandsConfig(sprintf('commands must not start with a slash. (%s)', $commandConfig['command']));
            }

            $processedCommands['/'.$commandConfig['command']] = $commandConfig['message'];
        }

        return new self($processedCommands);
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return $this->commands;
    }
}
