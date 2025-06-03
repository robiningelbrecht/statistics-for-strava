<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

final readonly class YamlConfigFile
{
    public function __construct(
        private string $filePath,
        private bool $isRequired,
        private bool $needsNestedProcessing,
        private ?string $prefix,
    ) {
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function needsNestedProcessing(): bool
    {
        return $this->needsNestedProcessing;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }
}
