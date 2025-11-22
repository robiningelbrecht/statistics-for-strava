<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

interface WebhookEventRepository
{
    public function add(WebhookEvent $webhookEvent): void;

    public function grab(): bool;
}
