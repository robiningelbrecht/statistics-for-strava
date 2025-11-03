<?php

namespace App\Tests\Controller;

use App\Controller\StravaWebhookController;
use App\Domain\Strava\Webhook\WebhookConfig;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StravaWebhookControllerTest extends ContainerTestCase
{
    private StravaWebhookController $controller;
    private MockObject $webhookConfig;
    private MockObject $commandBus;
    private MockObject $logger;

    public function testHandleValidation(): void
    {
        $this->webhookConfig
            ->method('isEnabled')
            ->willReturn(true);

        $this->webhookConfig
            ->method('getVerifyToken')
            ->willReturn('test-token');

        $response = $this->controller->handleValidation(new Request(
            query: [
                'hub.mode' => 'subscribe',
                'hub.challenge' => 'test-challenge-123',
                'hub.verify_token' => 'test-token',
            ],
        ));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(
            ['hub.challenge' => 'test-challenge-123'],
            Json::decode($response->getContent())
        );
    }

    public function testHandleValidationWhenDisabled(): void
    {
        $this->webhookConfig
            ->method('isEnabled')
            ->willReturn(false);

        $response = $this->controller->handleValidation(new Request());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testHandleValidationWithInvalidToken(): void
    {
        $this->webhookConfig
            ->method('isEnabled')
            ->willReturn(true);

        $this->webhookConfig
            ->method('getVerifyToken')
            ->willReturn('correct-token');

        $response = $this->controller->handleValidation(new Request(
            query: [
                'hub.mode' => 'subscribe',
                'hub.challenge' => 'test-challenge',
                'hub.verify_token' => 'wrong-token',
            ],
        ));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testHandleEvent(): void
    {
        $this->webhookConfig
            ->method('isEnabled')
            ->willReturn(true);

        $payload = [
            'aspect_type' => 'create',
            'event_time' => 1516126040,
            'object_id' => 1360128428,
            'object_type' => 'activity',
            'owner_id' => 134815,
            'subscription_id' => 120475,
            'updates' => [],
        ];

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch');

        $response = $this->controller->handleEvent(new Request(
            content: Json::encode($payload),
        ));

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testHandleEventWhenDisabled(): void
    {
        $this->webhookConfig
            ->method('isEnabled')
            ->willReturn(false);

        $response = $this->controller->handleEvent(new Request());

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testHandleEventWithInvalidJson(): void
    {
        $this->webhookConfig
            ->method('isEnabled')
            ->willReturn(true);

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        // Should still return 200 to prevent Strava retries
        $response = $this->controller->handleEvent(new Request(
            content: 'invalid-json',
        ));

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    protected function setUp(): void
    {
        $this->controller = new StravaWebhookController(
            $this->webhookConfig = $this->createMock(WebhookConfig::class),
            $this->commandBus = $this->createMock(CommandBus::class),
            $this->logger = $this->createMock(LoggerInterface::class),
        );
    }
}
