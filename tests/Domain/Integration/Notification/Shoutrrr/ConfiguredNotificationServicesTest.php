<?php

namespace App\Tests\Domain\Integration\Notification\Shoutrrr;

use App\Domain\Integration\Notification\Shoutrrr\ConfiguredNotificationServices;
use App\Domain\Integration\Notification\Shoutrrr\ShoutrrrUrl;
use PHPUnit\Framework\TestCase;

class ConfiguredNotificationServicesTest extends TestCase
{
    public function testFromConfig(): void
    {
        $this->assertEquals(
            [
                ShoutrrrUrl::fromString('ntfy://username:password@ntfy.sh'),
                ShoutrrrUrl::fromString('ntfy://admin:admin@ntfy.sh/topic'),
                ShoutrrrUrl::fromString('discord://token@webhookid?thread_id=123456789'),
            ],
            iterator_to_array(ConfiguredNotificationServices::fromConfig(
                config: [
                    'ntfy://admin:admin@ntfy.sh/topic',
                    'discord://token@webhookid?thread_id=123456789',
                ],
                ntfyUrl: 'https://ntfy.sh',
                ntfyUsername: 'username',
                ntfyPassword: 'password',
            )),
        );
    }

    public function testFromConfigItShouldThrow(): void
    {
        $this->expectExceptionObject(new \RuntimeException('Notification service name must be a string'));

        ConfiguredNotificationServices::fromConfig(
            config: [
                [],
            ],
            ntfyUrl: 'https://ntfy.sh',
            ntfyUsername: 'username',
            ntfyPassword: 'password',
        );
    }
}
