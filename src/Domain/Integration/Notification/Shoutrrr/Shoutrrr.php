<?php

namespace App\Domain\Integration\Notification\Shoutrrr;

interface Shoutrrr
{
    public function send(ShoutrrrUrl $shoutrrrUrl, string $message, string $title): void;
}
