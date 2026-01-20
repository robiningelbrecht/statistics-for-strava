<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

use Cron\CronExpression;

final readonly class WebhookConfig
{
    private function __construct(
        private bool $enabled,
        private string $verifyToken,
        private CronExpression $cronExpression,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $isEnabled = $config['enabled'] ?? false;
        $cronExpression = new CronExpression('* * * * *');
        if (!$isEnabled) {
            return new self(
                enabled: false,
                verifyToken: '',
                cronExpression: $cronExpression,
            );
        }

        if (empty($config['verifyToken'])) {
            throw new InvalidWebhookConfig('"verifyToken" property cannot be empty.');
        }
        $checkInterval = $config['checkIntervalInMinutes'] ?? 1;
        if (!is_int($checkInterval)) {
            throw new InvalidWebhookConfig('"checkIntervalInMinutes" property must be valid integer between 1 and 60.');
        }
        if ($checkInterval < 1 || $checkInterval > 60) {
            throw new InvalidWebhookConfig('"checkIntervalInMinutes" property must be valid integer between 1 and 60.');
        }
        if (1 !== $checkInterval) {
            $cronExpression = new CronExpression(sprintf('*/%s * * * *', $checkInterval));
        }

        return new self(
            enabled: $isEnabled,
            verifyToken: $config['verifyToken'],
            cronExpression: $cronExpression,
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

    public function getCronExpression(): CronExpression
    {
        return $this->cronExpression;
    }
}
