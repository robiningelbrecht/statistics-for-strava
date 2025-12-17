<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

interface WebhookEventRepository
{
    public function add(WebhookEvent $webhookEvent): void;

    /**
     * @return WebhookEvent[]
     */
    public function grab(): array;

    public function guardThatTableExists(): void;
}
