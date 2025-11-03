<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

final readonly class WebhookConfig
{
    private function __construct(
        private bool $enabled,
        private string $callbackUrl,
        private string $verifyToken,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            enabled: $config['enabled'] ?? false,
            callbackUrl: $config['callbackUrl'] ?? '',
            verifyToken: $config['verifyToken'] ?? '',
        );
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    public function getVerifyToken(): string
    {
        return $this->verifyToken;
    }

    public function isConfigured(): bool
    {
        return $this->enabled
            && !empty($this->callbackUrl)
            && !empty($this->verifyToken);
    }
}
