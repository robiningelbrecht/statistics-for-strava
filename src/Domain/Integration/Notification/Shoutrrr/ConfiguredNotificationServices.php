<?php

namespace App\Domain\Integration\Notification\Shoutrrr;

final readonly class ConfiguredNotificationServices implements \IteratorAggregate
{
    public function __construct(
        /** @var ShoutrrrUrl[] */
        private array $shoutrrrUrls,
    ) {
    }

    /**
     * @param array<mixed> $config
     */
    public static function fromConfig(
        array $config,
        ?string $ntfyUrl,
        ?string $ntfyUsername,
        ?string $ntfyPassword): self
    {
        $configuredNotificationServices = [];
        if (!empty($ntfyUrl)) {
            // Make sure feature is BC with old ntfy config.
            $configuredNotificationServices[] = ShoutrrrUrl::fromDeprecatedNtfyConfig(
                ntfyUrl: $ntfyUrl,
                ntfyUsername: $ntfyUsername,
                ntfyPassword: $ntfyPassword
            );
        }

        foreach ($config as $notificationService) {
            if (!is_string($notificationService)) {
                throw new \RuntimeException('Notification service name must be a string');
            }
            $configuredNotificationServices[] = ShoutrrrUrl::fromString($notificationService);
        }

        return new self($configuredNotificationServices);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->shoutrrrUrls);
    }
}
