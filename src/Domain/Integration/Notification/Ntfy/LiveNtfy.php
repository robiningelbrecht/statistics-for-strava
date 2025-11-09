<?php

declare(strict_types=1);

namespace App\Domain\Integration\Notification\Ntfy;

use App\Infrastructure\ValueObject\String\Url;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final readonly class LiveNtfy implements Ntfy
{
    public function __construct(
        private Client $client,
        #[\SensitiveParameter]
        private ?NtfyUrl $ntfyUrl,
        #[\SensitiveParameter]
        private ?NtfyUsername $ntfyUsername,
        #[\SensitiveParameter]
        private ?NtfyPassword $ntfyPassword,
    ) {
    }

    /**
     * @param array<string> $tags
     */
    public function sendNotification(
        string $title,
        string $message,
        array $tags,
        ?Url $click,
        ?Url $icon,
    ): void {
        if (!$this->ntfyUrl) {
            return;
        }

        $headers = [
            'Content-Type' => 'text/plain',
            'Title' => $title,
            'Tags' => implode(',', $tags),
            'Click' => (string) $click,
            'Icon' => (string) $icon,
        ];

        if ($click) {
            $headers['Actions'] = sprintf('view, Open app, %s, clear=true;', $click);
        }

        if ($this->ntfyUsername && $this->ntfyPassword) {
            $headers['Authorization'] = 'Basic '.base64_encode($this->ntfyUsername.':'.$this->ntfyPassword);
        }

        $this->client->request(
            'POST',
            (string) $this->ntfyUrl,
            [
                RequestOptions::HEADERS => $headers,
                RequestOptions::BODY => $message,
            ]
        );
    }
}
