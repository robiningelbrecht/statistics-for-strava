<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

final readonly class WebhookConfig
{
    private function __construct(
        private bool $enabled,
        private string $verifyToken,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $isEnabled = $config['enabled'] ?? false;
        if ($isEnabled && empty($config['verifyToken'])) {
            throw new InvalidWebhookConfig('"verifyToken" property cannot be empty.');
        }

        return new self(
            enabled: $isEnabled,
            verifyToken: $config['verifyToken'] ?? '',
        );
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getVerifyToken(): string
    {
        return $this->verifyToken;
    }
}
