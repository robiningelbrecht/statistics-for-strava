<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

enum WebhookAspectType: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
}
