<?php

namespace App\Tests\Domain\Strava\Webhook;

use App\Domain\Strava\StravaClientId;
use App\Domain\Strava\StravaClientSecret;
use App\Domain\Strava\Webhook\WebhookSubscriptionException;
use App\Domain\Strava\Webhook\WebhookSubscriptionService;
use App\Infrastructure\Serialization\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebhookSubscriptionServiceTest extends TestCase
{
    private WebhookSubscriptionService $service;
    private MockObject $client;

    public function testCreateSubscriptionSuccess(): void
    {
        $this->client
            ->expects($this->once())
            ->method('post')
            ->with(
                'https://www.strava.com/api/v3/push_subscriptions',
                [
                    RequestOptions::FORM_PARAMS => [
                        'client_id' => 'test-client-id',
                        'client_secret' => 'test-client-secret',
                        'callback_url' => 'https://example.com/webhook',
                        'verify_token' => 'secret-token',
                    ],
                ]
            )
            ->willReturn(new Response(200, [], Json::encode(['id' => 12345])));

        $result = $this->service->createSubscription(
            'https://example.com/webhook',
            'secret-token'
        );

        $this->assertEquals(['id' => 12345], $result);
    }

    public function testCreateSubscriptionFailure(): void
    {
        $this->client
            ->expects($this->once())
            ->method('post')
            ->willThrowException(new RequestException(
                'Error',
                new \GuzzleHttp\Psr7\Request('POST', 'uri'),
                new Response(400, [], 'Bad Request')
            ));

        $this->expectException(WebhookSubscriptionException::class);
        $this->expectExceptionMessage('Failed to create webhook subscription');

        $this->service->createSubscription(
            'https://example.com/webhook',
            'secret-token'
        );
    }

    public function testViewSubscriptionSuccess(): void
    {
        $subscriptions = [[
            'id' => 12345,
            'resource_state' => 2,
            'application_id' => 67890,
            'callback_url' => 'https://example.com/webhook',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]];

        $this->client
            ->expects($this->once())
            ->method('get')
            ->with(
                'https://www.strava.com/api/v3/push_subscriptions',
                [
                    RequestOptions::QUERY => [
                        'client_id' => 'test-client-id',
                        'client_secret' => 'test-client-secret',
                    ],
                ]
            )
            ->willReturn(new Response(200, [], Json::encode($subscriptions)));

        $result = $this->service->viewSubscription();

        $this->assertEquals($subscriptions, $result);
    }

    public function testViewSubscriptionFailure(): void
    {
        $this->client
            ->expects($this->once())
            ->method('get')
            ->willThrowException(new RequestException(
                'Error',
                new \GuzzleHttp\Psr7\Request('GET', 'uri')
            ));

        $this->expectException(WebhookSubscriptionException::class);
        $this->expectExceptionMessage('Failed to view webhook subscription');

        $this->service->viewSubscription();
    }

    public function testDeleteSubscriptionSuccess(): void
    {
        $this->client
            ->expects($this->once())
            ->method('delete')
            ->with(
                'https://www.strava.com/api/v3/push_subscriptions/12345',
                [
                    RequestOptions::QUERY => [
                        'client_id' => 'test-client-id',
                        'client_secret' => 'test-client-secret',
                    ],
                ]
            )
            ->willReturn(new Response(204));

        $this->service->deleteSubscription(12345);

        // Test passes if no exception thrown
        $this->assertTrue(true);
    }

    public function testDeleteSubscriptionFailure(): void
    {
        $this->client
            ->expects($this->once())
            ->method('delete')
            ->willThrowException(new RequestException(
                'Error',
                new \GuzzleHttp\Psr7\Request('DELETE', 'uri'),
                new Response(404, [], 'Not Found')
            ));

        $this->expectException(WebhookSubscriptionException::class);
        $this->expectExceptionMessage('Failed to delete webhook subscription');

        $this->service->deleteSubscription(12345);
    }

    protected function setUp(): void
    {
        $this->service = new WebhookSubscriptionService(
            $this->client = $this->createMock(Client::class),
            StravaClientId::fromString('test-client-id'),
            StravaClientSecret::fromString('test-client-secret'),
        );
    }
}

