<?php

namespace App\Tests\Domain\Strava\Webhook;

use App\Domain\Strava\Webhook\WebhookConfig;
use PHPUnit\Framework\TestCase;

class WebhookConfigTest extends TestCase
{
    public function testFromArrayWithDefaults(): void
    {
        $config = WebhookConfig::fromArray([]);

        $this->assertFalse($config->isEnabled());
        $this->assertFalse($config->isConfigured());
        $this->assertEquals('', $config->getCallbackUrl());
        $this->assertEquals('', $config->getVerifyToken());
    }

    public function testFromArrayWithValues(): void
    {
        $config = WebhookConfig::fromArray([
            'enabled' => true,
            'callbackUrl' => 'https://example.com/webhook',
            'verifyToken' => 'secret-token',
        ]);

        $this->assertTrue($config->isEnabled());
        $this->assertTrue($config->isConfigured());
        $this->assertEquals('https://example.com/webhook', $config->getCallbackUrl());
        $this->assertEquals('secret-token', $config->getVerifyToken());
    }

    public function testIsConfiguredRequiresAllFields(): void
    {
        $configMissingUrl = WebhookConfig::fromArray([
            'enabled' => true,
            'verifyToken' => 'secret-token',
        ]);
        $this->assertFalse($configMissingUrl->isConfigured());

        $configMissingToken = WebhookConfig::fromArray([
            'enabled' => true,
            'callbackUrl' => 'https://example.com/webhook',
        ]);
        $this->assertFalse($configMissingToken->isConfigured());

        $configDisabled = WebhookConfig::fromArray([
            'enabled' => false,
            'callbackUrl' => 'https://example.com/webhook',
            'verifyToken' => 'secret-token',
        ]);
        $this->assertFalse($configDisabled->isConfigured());
    }
}

