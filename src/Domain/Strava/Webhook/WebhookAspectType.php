<?php

namespace App\Domain\Strava\Webhook;

enum WebhookAspectType: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
}
