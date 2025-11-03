<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

use App\Domain\Strava\StravaClientId;
use App\Domain\Strava\StravaClientSecret;
use App\Infrastructure\Serialization\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

final readonly class WebhookSubscriptionService
{
    private const string PUSH_SUBSCRIPTIONS_ENDPOINT = 'https://www.strava.com/api/v3/push_subscriptions';

    public function __construct(
        private Client $client,
        private StravaClientId $clientId,
        private StravaClientSecret $clientSecret,
    ) {
    }

    /**
     * Create a new webhook subscription.
     *
     * @return array{id: int}
     * @throws WebhookSubscriptionException
     */
    public function createSubscription(string $callbackUrl, string $verifyToken): array
    {
        try {
            $response = $this->client->post(self::PUSH_SUBSCRIPTIONS_ENDPOINT, [
                RequestOptions::FORM_PARAMS => [
                    'client_id' => (string) $this->clientId,
                    'client_secret' => (string) $this->clientSecret,
                    'callback_url' => $callbackUrl,
                    'verify_token' => $verifyToken,
                ],
            ]);

            return Json::decode($response->getBody()->getContents());
        } catch (ClientException|RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($response = $e->getResponse()) {
                $errorMessage = $response->getBody()->getContents();
            }
            throw new WebhookSubscriptionException('Failed to create webhook subscription: ' . $errorMessage);
        }
    }

    /**
     * View existing webhook subscription.
     *
     * @return array<array{id: int, resource_state: int, application_id: int, callback_url: string, created_at: string, updated_at: string}>
     * @throws WebhookSubscriptionException
     */
    public function viewSubscription(): array
    {
        try {
            $response = $this->client->get(self::PUSH_SUBSCRIPTIONS_ENDPOINT, [
                RequestOptions::QUERY => [
                    'client_id' => (string) $this->clientId,
                    'client_secret' => (string) $this->clientSecret,
                ],
            ]);

            return Json::decode($response->getBody()->getContents());
        } catch (ClientException|RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($response = $e->getResponse()) {
                $errorMessage = $response->getBody()->getContents();
            }
            throw new WebhookSubscriptionException('Failed to view webhook subscription: ' . $errorMessage);
        }
    }

    /**
     * Delete a webhook subscription.
     *
     * @throws WebhookSubscriptionException
     */
    public function deleteSubscription(int $subscriptionId): void
    {
        try {
            $this->client->delete(self::PUSH_SUBSCRIPTIONS_ENDPOINT . '/' . $subscriptionId, [
                RequestOptions::QUERY => [
                    'client_id' => (string) $this->clientId,
                    'client_secret' => (string) $this->clientSecret,
                ],
            ]);
        } catch (ClientException|RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($response = $e->getResponse()) {
                $errorMessage = $response->getBody()->getContents();
            }
            throw new WebhookSubscriptionException('Failed to delete webhook subscription: ' . $errorMessage);
        }
    }
}

