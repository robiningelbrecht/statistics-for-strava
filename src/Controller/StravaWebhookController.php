<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Strava\Webhook\ProcessWebhookEvent\ProcessWebhookEvent;
use App\Domain\Strava\Webhook\WebhookConfig;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
readonly class StravaWebhookController
{
    public function __construct(
        private WebhookConfig $webhookConfig,
        private CommandBus $commandBus,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Handle webhook validation GET request from Strava.
     * Strava will call this endpoint to verify the callback URL when creating a subscription.
     */
    #[Route(path: '/webhook/strava', methods: ['GET'], priority: 2)]
    public function handleValidation(Request $request): JsonResponse
    {
        if (!$this->webhookConfig->isEnabled()) {
            return new JsonResponse(['error' => 'Webhooks are not enabled'], Response::HTTP_NOT_FOUND);
        }

        // Strava sends these query parameters for validation
        $mode = $request->query->get('hub.mode');
        $challenge = $request->query->get('hub.challenge');
        $verifyToken = $request->query->get('hub.verify_token');

        $this->logger->info('Received Strava webhook validation request', [
            'mode' => $mode,
            'verify_token' => $verifyToken,
        ]);

        // Verify the token matches what we configured
        if ($verifyToken !== $this->webhookConfig->getVerifyToken()) {
            $this->logger->error('Invalid verify token received', [
                'expected' => $this->webhookConfig->getVerifyToken(),
                'received' => $verifyToken,
            ]);

            return new JsonResponse(['error' => 'Invalid verify token'], Response::HTTP_FORBIDDEN);
        }

        // Must echo back the challenge
        return new JsonResponse(['hub.challenge' => $challenge], Response::HTTP_OK);
    }

    /**
     * Handle webhook event POST request from Strava.
     * Strava will call this endpoint when events occur (activity created, updated, etc).
     */
    #[Route(path: '/webhook/strava', methods: ['POST'], priority: 2)]
    public function handleEvent(Request $request): Response
    {
        if (!$this->webhookConfig->isEnabled()) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = Json::decode($request->getContent());

            $this->logger->info('Received Strava webhook event', [
                'payload' => $payload,
            ]);

            // Process the event asynchronously to respond quickly (within 2 seconds)
            // Strava requires a 200 OK response within 2 seconds
            $this->commandBus->dispatch(new ProcessWebhookEvent($payload));

            // Return 200 OK immediately
            return new Response('', Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Error processing webhook event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Still return 200 to prevent Strava from retrying
            return new Response('', Response::HTTP_OK);
        }
    }
}
