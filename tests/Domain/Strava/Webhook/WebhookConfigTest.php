<?php

namespace App\Tests\Domain\Strava\Webhook;

use App\Domain\Strava\Webhook\InvalidWebhookConfig;
use App\Domain\Strava\Webhook\WebhookConfig;
use PHPUnit\Framework\TestCase;

class WebhookConfigTest extends TestCase
{
    public function testFromArrayWithDefaults(): void
    {
        $config = WebhookConfig::fromArray([]);

        $this->assertFalse($config->isEnabled());
        $this->assertEmpty($config->getVerifyToken());
    }

    public function testFromArrayWithValues(): void
    {
        $config = WebhookConfig::fromArray([
            'enabled' => true,
            'verifyToken' => 'secret-token',
        ]);

        $this->assertTrue($config->isEnabled());
        $this->assertEquals('secret-token', $config->getVerifyToken());
    }

    public function testItShouldThrowWhenEmptyVerifyToken(): void
    {
        $this->expectExceptionObject(new InvalidWebhookConfig('"verifyToken" property cannot be empty.'));
        WebhookConfig::fromArray([
            'enabled' => true,
        ]);
    }
}
