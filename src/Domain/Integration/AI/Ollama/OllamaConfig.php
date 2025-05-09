<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Ollama;

use App\Infrastructure\ValueObject\String\Url;

final readonly class OllamaConfig
{
    private function __construct(
        private ?string $model,
        private ?Url $url,
    ) {
    }

    public static function create(?string $model, ?Url $url): self
    {
        return new self(
            model: $model,
            url: $url
        );
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getUrl(): ?Url
    {
        return $this->url;
    }
}
